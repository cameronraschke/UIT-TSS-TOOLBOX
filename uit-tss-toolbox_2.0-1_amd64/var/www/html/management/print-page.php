<?php
include("/var/www/html/management/php/include.php");

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
        margin: 10% 0% 5% 0%;
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
        We hope that you enjoy your new laptop. We have preinstalled a few apps for you, 
        including Microsoft Office, Microsoft Teams, Microsoft Outlook, Chrome, Edge, Firefox, 
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
        To login to this laptop, please use the following credentials:
        <br>Username: <b><u>Student</u></b>
        <br>Password: <b><u>UHouston!</u></b>
      </p>
    </div>
    <div class="page-content" style="border-bottom: 1px solid black;">
      <p>
        When you eventually return this laptop back to our office, 
        we make sure that all of your data is completely and securely erased.
      </p>
    </div>
    <div class="tagnumber">
        <h2>The number associated with this laptop is <b><?php echo htmlspecialchars($tagnumber); ?></b></h2>
    </div>
    <div class="page-content">
      <p>
        <i>
          By receiving this laptop as part of UIT's laptop checkout program, I
          agree to the following: 
          <ul>
            <li>I acknowledge that this laptop is the property of the State of Texas.
            <li>I agree to follow all federal, state, and local laws as well as UH 
                policies regarding proper usage of this laptop.
            <li>I agree to return this laptop, charger, and all other checked out accessories 
                by the return date written below.
            <li>I agree to treat this laptop with respect, to not modify the hardware 
                of this laptop, and to reach out to UIT Support in case I have any questions or concerns 
                (contact information below).
          </ul>
        </i>
      </p>
    </div>
    <div class="signature">
      <div>
        <?php
        if (strFilter($_GET["customer_name"]) === 0) {
            echo "<p>Customer Name: <span class='customer-info' style='width: 10%'>" . htmlspecialchars($_GET["customer_name"]) . "</span></p>" . PHP_EOL;
        } else {
            echo "<p>Customer Name: _________________________________________</p>" . PHP_EOL;
        }
        ?>
      </div>
      <div style="margin-left: 3%;">
        <?php
        if (strFilter($_GET["checkout_date"]) === 0) {
            echo "<p>Checkout Date: <span class='customer-info'>" . htmlspecialchars($_GET["checkout_date"]) . "</span></p>" . PHP_EOL;
        } else {
            echo "<p>Checkout Date: ___________________</p>" . PHP_EOL;
        }
        ?>
      </div>
    </div>
    <div class="signature">
      <div>
        <p>Customer MyUH/Peoplesoft ID: ___________________________</p>
      </div>
      <div style="margin-left: 3%;"> 
        <p>Return Date: ______________________</p>
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