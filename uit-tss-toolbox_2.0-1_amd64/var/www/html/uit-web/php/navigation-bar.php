<div class='navigation-bar'>
<ul>
<?php 
  // Index
  echo "<li><a href='/index.php'>Home</a></li>";

  // Job Queue
  if ($_SERVER['PHP_SELF'] == "/job-queue.php") {
    echo "<li class='active-navigation-bar'><a href='/job-queue.php'>Job Queue</a></li>";
  } else {
    echo "<li><a href='/job-queue.php'>Job Queue</a></li>";
  }

  // Locations
  if ($_SERVER['PHP_SELF'] == "/locations.php") {
    echo "<li class='active-navigation-bar'><a href='/locations.php'>Locations</a></li>";
  } else {
    echo "<li><a href='/locations.php'>Locations</a></li>";
  }

  // Checkouts
  if ($_SERVER['PHP_SELF'] == "/checkouts.php") {
    echo "<li class='active-navigation-bar'><a href='/checkouts.php'>Checkouts</a></li>";
  } else {
    echo "<li><a href='/checkouts.php'>Checkouts</a></li>";
  }

  // Reports
  if ($_SERVER['PHP_SELF'] == "/serverstats.php") {
    echo "<li class='active-navigation-bar'><a href='/serverstats.php'>Reports</a></li>";
  } else {
    echo "<li><a href='/serverstats.php'>Reports</a></li>";
  }
  
  echo "<li style='float: right; margin-right: 4em;'><a href='/logout.php'>Logout</a></li>";
  echo "<div class='location-form' style='float: right; width: 20em; margin: 0.5em 3em 0.5em 3em;'><form method='GET' action='/tagnumber.php'><input type='text' id='tagnumber-search' name='tagnumber' style='width: 80%;' autocapitalize='none' autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Enter Tag Number...'><button style='border: none;'><img class='icon' src='/images/search.svg'></button></form></div>";
?>
</ul>
</div>

<div class='menubar'>
  <?php
    if ($_SERVER['PHP_SELF'] == "/index.php") {
      echo "<h1 class='header-title'>UIT-Web Client Management Site</h1>";
      echo "<h2 class='header-title'>Contact WEBMASTER_NAME for Assistance: </h2>";
      echo "<h3 class='header-title'><img class='icon' src='/images/new-tab.svg'><a href='mailto:WEBMASTER_EMAIL?subject=UIT-TC-MGMT%20Assistance%20with%20" . $_SERVER["REQUEST_URI"] . "'>Send Email</a></h3>";
    }

    if ($_SERVER['PHP_SELF'] == "/job-queue.php") {
      echo "<h1 class='header-title'>Job Queue</h1>";
      echo "<h2 class='header-title'>Queue and view active jobs for clients plugged into the server</h2>";
      echo "<h3 class='header-title'><img class='icon' src='/images/new-tab.svg'><a href='/documentation/pages.php?page=job-queue' target='_blank'>Documentation</a></h3>";
    }

    if ($_SERVER['PHP_SELF'] == "/locations.php") {
      echo "<h1 class='header-title'>Update/View Locations</h1>";
      echo "<h2 class='header-title'>Filter and update client information</h2>";
      echo "<h3 class='header-title'><img class='icon' src='/images/new-tab.svg'><a href='/documentation/pages.php?page=locations' target='_blank'>Documentation</a></h3>";
    }

    if ($_SERVER['PHP_SELF'] == "/tagnumber.php") {
      echo "<h1 class='header-title'>Detailed Client Info</h1>";
      echo "<h2 class='header-title'>View and queue client jobs and detailed client information</h2>";
      echo "<h3 class='header-title'><img class='icon' src='/images/new-tab.svg'><a href='/documentation/pages.php?page=tagnumber' target='_blank'>Documentation</a></h3>";
    }

    if ($_SERVER['PHP_SELF'] == "/checkouts.php") {
      echo "<h1 class='header-title'>Checkout History</h1>";
      echo "<h2 class='header-title'>View clients that are currently checked out</h2>";
      echo "<h3 class='header-title'><img class='icon' src='/images/new-tab.svg'><a href='/documentation/pages.php?page=checkouts' target='_blank'>Documentation</a></h3>";
    }

    if ($_SERVER['PHP_SELF'] == "/serverstats.php") {
      echo "<h1 class='header-title'>Daily Reports</h1>";
      echo "<h2 class='header-title'>Daily reports back to January 2023</h2>";
      echo "<h3 class='header-title'><img class='icon' src='/images/new-tab.svg'><a href='/documentation/pages.php?page=serverstats' target='_blank'>Documentation</a></h3>";
    }

    // DOCUMENTATION
    if ($_SERVER['REQUEST_URI'] == "/documentation/pages.php?page=supported-emojis") {
      echo "<h1 class='header-title'>Supported Emojis</h1>";
      echo "<h2 class='header-title'>Type the left column's characters in order to get the corresponding emoji. Only works for notes on <a href='/index.php'>the home page</a>.</h2>";
    }
    if ($_SERVER['REQUEST_URI'] == "/documentation/pages.php?page=locations") {
      echo "<h1 class='header-title'>Locations Documentation</h1>";
      echo "<h2 class='header-title'>Go to <a href='/locations.php'>/locations.php</a>.</h2>";
    }
    if ($_SERVER['REQUEST_URI'] == "/documentation/pages.php?page=kernel-update") {
      echo "<h1 class='header-title'>Kernel Update Documentation</h1>";
    }
  ?>
</div>

<?php 
if ($_SERVER['PHP_SELF'] != "/index.php") {
  echo "<div class='scroll-to-top'><button onclick='scrollToTop();'><img class='scroll-icon' src='/images/scroll-to-top.svg'></button></div>";
}
?>