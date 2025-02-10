<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<head>
    <meta name="x-apple-disable-message-reformatting">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?= lang('Auth.magicLinkSubject') ?></title>
</head>

<body>
    <p>Hi, <?= auth()->user()->username ?>,</p>
    <p>Welcome to <?= setting('Email.fromName') ?>! We’re thrilled to have you join us. Whether you're hosting epic tournaments or cheering for your favorites, we’ve got everything you need to make competitions legendary.</p>
    <br />
    <p>🔥 Create Tournaments – Choose from Single, Double, or Knockout elimination styles.</p>
    <p>🎨 Customize Your Experience – Personalize themes, add images, and even play audio or video for dramatic bracket reveals.</p>
    <p>🗳️ Engage with Votes – Let others vote for participants to determine winners.</p>
    <p>🔗 Share & Manage Permissions – Easily share tournaments and control access levels.</p>
    <p>🥇 Track the Leaderboard – See top competitors and explore public tournaments in the gallery</p>
    <p>✨️And much more!</p>
    <br />
    <p>🚀 Your journey starts now—<a href="<?= url_to('tournaments/create') ?>" style="color: #ffffff; font-size: 16px; font-family: Helvetica, Arial, sans-serif; text-decoration: none; border-radius: 6px; line-height: 20px; display: inline-block; font-weight: normal; white-space: nowrap; background-color: #0d6efd; padding: 8px 12px; border: 1px solid #0d6efd;">Click Here to Create Your First Tournament</a></p>
    <br />
    <p>If you have any questions, feel free to reply back to this email and we'll respond accordingly. 😊</p>
    <br />
    <p>⚔️ Let the games begin!</p>
</body>

</html>