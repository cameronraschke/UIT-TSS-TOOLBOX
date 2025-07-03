<?php
include("/var/www/html/uit-web/php/include.php");

if ($_GET["password"] !== "WEB_SVC_PASSWD") {
  exit();
}

$db = new db();

$db->Pselect("SELECT t1.last_job_time,
locations.tagnumber, locations.time, system_data.system_model,
t1.system_serial
FROM locations
LEFT JOIN
  (SELECT tagnumber, system_serial, ROW_NUMBER() OVER(PARTITION BY tagnumber ORDER BY time DESC) AS 'row_count', IF(SUBSTRING(time, 12, 24) = '00:00:00.000', NULL, DATE_FORMAT(time, '%b %D, %Y at %r')) AS 'last_job_time'
    FROM jobstats
    WHERE (erase_completed = 1 OR clone_completed = 1)
  ) t1 ON t1.tagnumber = locations.tagnumber
INNER JOIN system_data ON system_data.tagnumber = t1.tagnumber
  AND t1.row_count = 1
  AND locations.time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber)
  AND locations.tagnumber = :tagnumber
  ORDER BY locations.time DESC", array(':tagnumber' => $_GET["tagnumber"]));

  if(arrFilter($db->get()) === 0) {
    foreach ($db->get() as $key => $value) {
      $tagnumber = $value["tagnumber"];
      $lastJobTime = $value["last_job_time"];
      $systemModel = $value["system_model"];
      $serial = $value["system_serial"];
    }
  }

  $customerName = htmlspecialchars_decode($_GET["customer_name"]);
  $checkoutDate = htmlspecialchars_decode($_GET["checkout_date"]);
  $customerPSID = htmlspecialchars_decode($_GET["customer_psid"]);
  $returnDate = htmlspecialchars_decode($_GET["return_date"]);
?>

<html>
  <head>
    <style>
      * {
        font-family: sans-serif;
      }
      body {
        margin: 0% 7% 7% 7%;
      }
      .uh-logo {
        margin: 5% 0% 5% 0%;
        max-width: 100%;
        max-height: 20%;
      }
      .uh-logo img {
        max-width: 100%;
        height: auto;
      }
      .page-content {
        margin: 1% 1% 1% 1%;
      }
      .page-content h1 {
        text-align: center;
        font-size: 28px;
      }
      .page-content p {
        text-align: center;
        font-size: 18px;
      }
      .tagnumber {
        margin: 3% 0% 3% 0%;
        font-size: 24px;
        text-align: center;
      }
      .signature {
        margin: 1% 0% 1% 0%;
        text-align: left;
        font-size: 18px;
        width: auto;
      }
      .signature div {
        display: inline-block;
      }
      .signature p {
        display: table;
      }
      .signature span {
        display: table-cell;
        width: 100%;
        border-bottom: 2px solid black;
        text-align: center;
      }
      .customer-info {
        font-size: 22px;
        border-bottom: 1px solid black;
      }
      .contact-info {
        text-align: left;
      }
      .footer {
        text-align: left;
        bottom: 5%;
      }
    </style>
  </head>
  <body>
    <div class="uh-logo">
      <img src="/images/uh-logo.png">
    </div>
    <div class="page-content">
      <h1><b>University Information Technology (UIT) - Laptop Checkout Program</b></h1>
    </div>
    <div class="page-content">
      <p>
        We hope that you enjoy using this laptop. We have pre-installed a few apps for you, 
        including Microsoft Office, Microsoft Outlook, Microsoft Teams, Zoom, Chrome, Edge, Firefox, 
        LockDown Browser, and Cisco Secure Client (VPN).
      </p>
    </div>
    <div class="page-content"> 
      <p>
        Please treat our laptops with respect. Many other UH community members 
        will use these laptops after you, so we ask that you return this laptop 
        in the same condition in which you received it.
      </p>
    </div>
    <div class="page-content">
      <p>
        <!--To login to this laptop, please use the following credentials:
        <br>Username: <b><u>Student</u></b>
        <br>Password: <b><u>UHouston!</u></b>-->
        To login to this laptop, please use your <b><u>CougarNet ID</u></b> and <b><u>CougarNet Password</u></b>
      </p>
    </div>
    <div class="page-content" style="border-bottom: 1px solid black;">
      <p>
        When you return this laptop back to our office, 
        we make sure that all of your data is completely and securely erased.
      </p>
    </div>
    <div class="tagnumber">
        <h2>The number associated with this laptop is <b><?php echo htmlspecialchars($tagnumber); ?></b></h2>
    </div>
    <div class="page-content">
      <p>
        <i>
          In receiving this laptop loan from UIT, I agree to the following: 
          <ul>
            <li>I acknowledge that this laptop is the property of the State of Texas.
            <li>I agree to follow all federal, state, and local laws as well as UH 
                policies regarding proper usage of this laptop.
            <li>I agree to return this laptop, charger, and all other accessories that were loaned to me 
                by the return date written below. UIT reserves the right to modify the return
                date at any time.
            <li>I agree to treat this laptop with respect, to not modify the hardware 
                of this laptop, and to contact UIT Support with any questions or concerns.
                (contact information below).
          </ul>
        </i>
      </p>
    </div>
    <div class="signature">
      <div style="width: 60%">
        <p>Customer Name: <span><?php echo htmlspecialchars($customerName); ?></span></p>
      </div>
      <div style="width: 30%; margin-left: 3%;">
        <p>Checkout Date: <span><?php echo htmlspecialchars($checkoutDate); ?></span></p>
      </div>
    </div>
    <div class="signature">
      <div style="width: 45%">
        <p>Customer MyUH/PSID: <span><?php echo htmlspecialchars($customerPSID); ?></span></p>
      </div>
      <div style="width: 45%; margin-left: 3%;">
        <p>Return Date: <span><?php echo htmlspecialchars($returnDate); ?></span></p>
      </div>
    </div>
    <div class="signature">
      <div style="width: 60%">
        <p>Customer Signature: <span></span></p>
      </div>
      <div style="width: 30%; margin-left: 3%;">
        <p>Date: <span></span></p>
      </div>
    </div>
    <div class="contact-info">
      <p>
      Please reach out to our office with any questions or concerns:
      <br><b>Phone</b>: (713) 743-1411
      <br><b>Email</b>: uitsupport@uh.edu
      <br><b>Live Chat</b>: https://gethelp.uh.edu/live_chat (Select UIT Support Center)
    </p>
    </div>
    <div class="footer">
      <p>
        <br>Laptop Model: <?php echo htmlspecialchars($systemModel); ?>
        <br>Laptop Serial Number: <?php echo htmlspecialchars($serial); ?>
        <br>Laptop Imaged on <?php echo htmlspecialchars($lastJobTime); ?>
      </p>
    </div>
  </body>
</html>