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
    <p>You've been invited to join the tournament <strong><?= $tournament->name ?></strong> (<?= base_url("tournament/$tournament->id/view") ?>)!</p>

    <p>Get ready to compete and showcase your skills.</p>

    <?php $user = auth()->user() ? auth()->getProvider()->findById(auth()->user()->id) : null; ?>
    🔹 <strong>Added By</strong>: <?= $user ? "$user->username ($user->email)" : "Guest User" ?><br />
    🔹 <strong>Your Role</strong>: Participant

    <p>Prepare yourself for an exciting competition. If you weren’t expecting this invitation, you can ignore this email.</p>

    <p>See you in the brackets!</p>

    <p>Best regards,</p>
    <p>🏆 <?= esc($tournamentCreatorName) ?>Team</p>
    <br />
    <p>Disclaimer: To opt out of these emails, login and adjust the notification setting from the "bell" icon.</p>
</body>

</html>