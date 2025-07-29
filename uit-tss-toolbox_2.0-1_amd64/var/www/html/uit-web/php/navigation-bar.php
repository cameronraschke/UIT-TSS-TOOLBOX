<div class='navigation-bar'>
<?php 
  // Index
  echo "<div><a href='/index.php'>Home</a></div>";

  // Job Queue
  echo "<div>";
  if ($_SERVER['PHP_SELF'] == "/job-queue.php") {
    echo "<a href='/job-queue.php'>Job Queue</a>";
  } else {
    echo "<a href='/job-queue.php'>Job Queue</a>";
  }
  echo "</div>";

  // Locations
  echo "<div>";
  if ($_SERVER['PHP_SELF'] == "/locations.php") {
    echo "<a href='/locations.php'>Locations</a>";
  } else {
    echo "<a href='/locations.php'>Locations</a>";
  }
  echo "</div>";

  // Checkouts
  echo "<div>";
  if ($_SERVER['PHP_SELF'] == "/checkouts.php") {
    echo "<a href='/checkouts.php'>Checkouts</a>";
  } else {
    echo "<a href='/checkouts.php'>Checkouts</a>";
  }
  echo "</div>";

  // Reports
  echo "<div>";
  if ($_SERVER['PHP_SELF'] == "/serverstats.php") {
    echo "<a href='/serverstats.php'>Reports</a>";
  } else {
    echo "<a href='/serverstats.php'>Reports</a>";
  }
  echo "</div>";

  //echo "<div><form method='GET' action='/tagnumber.php'><input type='text' id='tagnumber-search' name='tagnumber' autocapitalize='none' autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Enter Tag Number...'><button><img class='icon' src='/images/search.svg'></button></form><div id='dropdown-search' class='dropdown-search'></div></div>";
  //echo "<div><a id='logout' style='margin-left: 2vw; margin-right: 2vw;' href='#'>Logout</a></div>";

?>

    <div><a id='logout' href='#'>Logout</a></div>
  

    <div id='tagnumber-search-spacer'></div>
    <div id='tagnumber-search-div'><form method='GET' action='/tagnumber.php'><div style='display: flex; justify-content: center; align-items: center;'><input type='text' id='tagnumber-search' name='tagnumber' autocapitalize='none' autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Enter Tag Number...'><button><img class='icon' src='/images/search.svg'></button></div></form><div id='dropdown-search' class='dropdown-search'></div></div>

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

    if ($_SERVER['PHP_SELF'] == "/view-images.php") {
      if (isset($_GET["live_image"]) && $_GET["live_image"] == "1") {
        echo "<h1 class='header-title'>Live View - " . htmlspecialchars($_GET["tagnumber"]) . "</h1>";
      } else {
        echo "<h1 class='header-title'>View Client Images - " . htmlspecialchars($_GET["tagnumber"]) . "</h1>";
      }
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
  echo "<div class='scroll-to-top'><button onclick='scrollToTop();'><img src='/images/scroll-to-top.svg'></button></div>";
}
?>