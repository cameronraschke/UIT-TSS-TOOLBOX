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
                in the same condition that you checked it out in.
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
            <h2>The number associated with your laptop is <b><?php echo $_GET["tagnumber"]; ?></b></h2>
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
                if ($_GET["customer_name"]) {
                    echo "<p>Customer Name: <span class='customer-info' style='width: 10%'>" . htmlspecialchars($_GET["customer_name"]) . "</span></p>" . PHP_EOL;
                } else {
                    echo "<p>Customer Name: _________________________________________</p>" . PHP_EOL;
                }
                ?>
            </div>
            <div style="margin-left: 3%;">
                <?php
                if ($_GET["checkout_date"] !== "") {
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
                <br>Laptop Model: <?php echo htmlspecialchars($_GET["system_model"]); ?>
                <br>Laptop Serial Number: <?php echo htmlspecialchars($_GET["system_serial"]); ?>
                <br>Laptop Imaged on <?php echo htmlspecialchars($_GET["date_formatted"]); ?> at <?php echo htmlspecialchars($_GET["time_formatted"]); ?>
            </p>
        </div>

    </body>

</html>