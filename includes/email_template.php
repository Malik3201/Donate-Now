<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/ui_helpers.php';

function render_email_template(string $title, string $message, ?string $buttonText = null, ?string $buttonUrl = null): string
{
    $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $logoUrl = htmlspecialchars(app_email_logo_url(), ENT_QUOTES, 'UTF-8');
    $homeUrl = htmlspecialchars(APP_URL . '/index.php', ENT_QUOTES, 'UTF-8');
    $year = date('Y');

    $buttonBlock = '';
    if ($buttonText !== null && $buttonText !== '' && $buttonUrl !== null && $buttonUrl !== '') {
        $btnEsc = htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8');
        $urlEsc = htmlspecialchars($buttonUrl, ENT_QUOTES, 'UTF-8');
        $buttonBlock = '
          <tr>
            <td style="padding:0 40px 36px;">
              <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto;">
                <tr>
                  <td align="center" style="border-radius:999px;background:#b65e3c;box-shadow:0 12px 28px rgba(142,67,42,0.35);">
                    <a href="' . $urlEsc . '" target="_blank" style="display:inline-block;padding:14px 32px;font-family:Inter,Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;color:#fffaf4;text-decoration:none;">' . $btnEsc . '</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>';
    }

    return '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>' . $titleEsc . '</title>
  <style type="text/css">
    body{margin:0;padding:0;width:100%!important;-webkit-text-size-adjust:100%;}
    img{border:0;height:auto;line-height:100%;outline:none;text-decoration:none;}
    table{border-collapse:collapse!important;}
    @media only screen and (max-width:620px){
      .dn-email-pad{padding-left:22px!important;padding-right:22px!important;}
      .dn-email-title{font-size:22px!important;}
    }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#f3ebe0;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f3ebe0;">
    <tr>
      <td align="center" style="padding:32px 16px 40px;">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;">
          <tr>
            <td style="padding:0 0 18px;text-align:center;">
              <a href="' . $homeUrl . '" target="_blank" style="text-decoration:none;">
                <img src="' . $logoUrl . '" width="56" height="56" alt="Donate Now" style="display:block;width:56px;height:56px;border-radius:14px;margin:0 auto;">
              </a>
            </td>
          </tr>
          <tr>
            <td style="background:#fffaf4;border-radius:24px;border:1px solid #e8ddd0;box-shadow:0 24px 60px rgba(43,33,27,0.1);">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                  <td style="padding:28px 40px 22px;background:#2b211b;">
                    <p style="margin:0 0 8px;font-family:Inter,Arial,Helvetica,sans-serif;font-size:12px;font-weight:600;color:#d9b78f;letter-spacing:0.1em;text-transform:uppercase;">Donate Now</p>
                    <h1 class="dn-email-title" style="margin:0;font-family:Inter,Arial,Helvetica,sans-serif;font-size:26px;line-height:1.25;font-weight:800;color:#fffaf4;">' . $titleEsc . '</h1>
                  </td>
                </tr>
                <tr>
                  <td class="dn-email-pad" style="padding:32px 40px 8px;font-family:Inter,Arial,Helvetica,sans-serif;font-size:16px;line-height:1.65;color:#4a3f38;">
                    ' . $message . '
                  </td>
                </tr>
                ' . $buttonBlock . '
                <tr>
                  <td class="dn-email-pad" style="padding:8px 40px 28px;font-family:Inter,Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#75685e;">
                    If you did not expect this email, you can safely ignore it. Need help? <a href="' . $homeUrl . '" style="color:#b65e3c;font-weight:600;text-decoration:none;">Visit Donate Now</a>.
                  </td>
                </tr>
                <tr>
                  <td style="padding:18px 40px 24px;background:#f5efe6;border-top:1px solid #e8ddd0;font-family:Inter,Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#9a8b80;text-align:center;">
                    &copy; ' . $year . ' Donate Now &middot; Transparent local giving
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:20px 12px 0;font-family:Inter,Arial,Helvetica,sans-serif;font-size:11px;color:#9a8b80;text-align:center;">
              Automated message from your Donate Now account.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}
