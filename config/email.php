<?php
// SMTP/email configuration for application
// Fill these with your Gmail SMTP credentials (use an App Password if 2FA enabled)
return [
    // SMTP server (Gmail)
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'rgb.dempsey@gmail.com', // SMTP username (Gmail address)
    'password' => 'hyig miyw khaa nynx', // <-- set your SMTP password or app password here
    'secure' => 'tls', // 'tls' or 'ssl'

    // From address/name used in messages
    'from_email' => 'rgb.dempsey@gmail.com',
    'from_name' => 'Maata Fish Farm'
];

// https://myaccount.google.com/apppasswords