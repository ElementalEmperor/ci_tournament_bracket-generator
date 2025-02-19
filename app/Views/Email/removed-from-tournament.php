<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<head>
    <meta name="x-apple-disable-message-reformatting">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?= lang('Auth.magicLinkSubject') ?></title>
</head>

<body>
    <p>Hi <?= esc($username) ?>,</p>
    <p>We wanted to inform you that you have been removed from the tournament <strong><?= $tournament->name ?></strong> (<?= base_url("tournament/$tournament->id/view") ?>)!</p>

    <?php $user = auth()->user() ? auth()->getProvider()->findById(auth()->user()->id) : null; ?>
    🔹 <strong>Removed By</strong>: <?= $user ? "$user->username ($user->email)" : "Guest User" ?><br />

    <p>If you believe this was a mistake or have any questions, try contacting the tournament organizer. <?= $creator ? "($creator->email)" : '' ?> </p>

    <p>Best regards,</p>
    <p>🏆 <?= esc($tournamentCreatorName) ?>Team</p>
    <br />
    <p>Disclaimer: To opt out of these emails, login and adjust the notification setting from the "bell" icon.
    </p>
</body>

</html>