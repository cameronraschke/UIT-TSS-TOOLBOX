function newLocationWindow(location, tagnumber, department = undefined, domain = undefined) {
  //openedWindow = window.open("/locations.php?location=" + location + "&tagnumber=" + tagnumber);
  if (location && tagnumber) {
    if (department) {
      openedWindow = window.location.assign("/locations.php?ref=1&location=" + location + "&tagnumber=" + tagnumber + "&department=" + department);
    } else if (domain) {
      openedWindow = window.location.assign("/locations.php?ref=1&location=" + location + "&tagnumber=" + tagnumber + "&domain=" + domain);
    } else {
      openedWindow = window.location.assign("/locations.php?ref=1&location=" + location + "&tagnumber=" + tagnumber);
    }
  }
};

// function closeLocationWindow() {
//   openedWindow.close();
// }

// openedWindow.addEventListener('DOMContentLoaded', () => {
//     const closeButton = openedWindow.document.getElementById('closeButton');
//     closeButton.addEventListener('click', () => {
//         window.close();
//     });
// });

function openImage(imageData) {
  const byteCharacters = atob(imageData);
  const byteNumbers = new Array(byteCharacters.length).fill().map((_, i) => byteCharacters.charCodeAt(i));
  const byteArray = new Uint8Array(byteNumbers);
  const blob = new Blob([byteArray], { type: "image/jpeg" });
  const blobUrl = URL.createObjectURL(blob);
  //window.open(blobUrl);
  window.location.href = blobUrl;
}


function scrollToTop() {
  window.scrollTo(0, 0);
}

function getCursorPos(myElement) {
  let startPosition = myElement.selectionStart;
  let endPosition = myElement.selectionEnd;

  myElement.focus();
  return(startPosition);
}


// Autofill tag numbers
async function autoFillTags(input) {
  
document.getElementById('dropdown-search').style.display = "none";
document.getElementById('dropdown-search').innerHTML = "";

tagStr = input.toString();
let availableTagnumbers = tagStr.split('|');

document.querySelector('body').addEventListener('click', () => {
  if (document.activeElement !== document.getElementById('tagnumber-search') && document.activeElement !== document.getElementById('tagnumber-search')) {
      document.getElementById('dropdown-search').style.display = "none";
      document.getElementById('dropdown-search').innerHTML = "";
  }
});

  var tagnumberField = document.getElementById('tagnumber-search');

  tagnumberField.addEventListener('keyup', (event) => {
    const inputText = tagnumberField.value;

    if (event.key === 'Backspace' || event.key === 'Delete') {
      tagnumberField.value = tagnumberField.value.substr(0, getCursorPos(tagnumberField));
      tagnumberField.setSelectionRange(getCursorPos(tagnumberField), getCursorPos(tagnumberField));
    }
  });

  tagnumberField.addEventListener('input', function() {
    const inputText = tagnumberField.value;
    var re = new RegExp('^' + inputText, 'gi');
    var re1 = new RegExp('^' + inputText + '$', 'gi');
    const matchingExact = availableTagnumbers.find(suggestion => suggestion.match(re1));
    const matchingSuggestion = availableTagnumbers.find(suggestion => suggestion.match(re));
    const allMatches = availableTagnumbers.filter((suggestionList) => suggestionList.match(re))

    if (allMatches.length > 0 && inputText.length > 0) {
      document.getElementById('dropdown-search').style.display = "inline-block";
      dropdownHTML = "<div>";
      allMatches.slice(0,6).forEach(element => {
        if (allMatches[0] == element) {
          dropdownHTML += "<div><a style='color: black;' href='/tagnumber.php?tagnumber=" + element + "'>" + element + "</a>" + "</div>";
        } else {
          dropdownHTML += "<div><a style='color: black;' href='/tagnumber.php?tagnumber=" + element + "'>" + element + "</a>" + "</div>";
        }
      });
      dropdownHTML += "</div>";
      document.getElementById('dropdown-search').innerHTML = dropdownHTML;
    } else {
      document.getElementById('dropdown-search').style.display = "none";
      document.getElementById('dropdown-search').innerHTML = "";
    }

    if (matchingSuggestion && inputText.length > 0) {
      if (matchingExact) {
        tagnumberField.value = matchingExact;
      } else {
        tagnumberField.value = matchingSuggestion;
      }
      tagnumberField.setSelectionRange(inputText.length, matchingSuggestion.length);
    }
  })
};


function logout() {
  document.cookie = "authCookie=";
  localStorage.removeItem('bearerToken');
  localStorage.removeItem('authStr');
  localStorage.removeItem('basicToken');
  window.location.href = "/logout.php";
};

const authChannel = new BroadcastChannel('auth');
authChannel.onmessage = function(event) {
  console.log(event);
  if (event.data.cmd === 'logout') {
    logout();
  }
};

const button = document.querySelector('#logout');
button.addEventListener('click', e => {
  console.log('logout');
  authChannel.postMessage({cmd: 'logout'});
  logout();
});

function test() { 
  console.log('test');
};


async function updateRemoteOfflineTable() {
  try {
    const oldRemoteOfflineTable = document.getElementById('remoteOfflineTable');
    const remoteOfflineTable = document.createElement('table');
    remoteOfflineTable.setAttribute('id', 'remoteOfflineTable');
    remoteOfflineTable.style.alignContent = 'left';

    let tableHeader
    tableHeader = document.createElement("thead");
    let tableHeaderRow = document.createElement('tr');

    var cell = document.createElement("th");
    cell.innerText = "Offline Clients";
    tableHeaderRow.appendChild(cell);

    var cell = document.createElement("th");
    cell.innerText = "Last Heard";
    tableHeaderRow.appendChild(cell);

    var cell = document.createElement("th");
    cell.innerText = "Last Location";
    tableHeaderRow.appendChild(cell);

    var cell = document.createElement("th");
    cell.innerText = "Last Status";
    tableHeaderRow.appendChild(cell);

    var cell = document.createElement("th");
    cell.innerText = "OS Installed";
    tableHeaderRow.appendChild(cell);

    var cell = document.createElement("th");
    cell.innerText = "Battery Charge";
    tableHeaderRow.appendChild(cell);

    var cell = document.createElement("th");
    cell.innerText = "CPU Temp";
    tableHeaderRow.appendChild(cell);

    var cell = document.createElement("th");
    cell.innerText = "Disk Temp";
    tableHeaderRow.appendChild(cell);

    var cell = document.createElement("th");
    cell.innerText = "Power Draw";
    tableHeaderRow.appendChild(cell);

    tableHeader.appendChild(tableHeaderRow);


    var tableBody
    var tableBodyData
    tableBody = document.createElement("tbody");
    tableBodyData = await fetchData('https://WAN_IP_ADDRESS:31411/api/remote?type=remote_offline');

    Object.entries(tableBodyData).forEach(([key, value]) => {
      let tableBodyRow = document.createElement("tr");

      var cell = document.createElement("td");
      cell.innerText = value["tagnumber"];
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["time_formatted"];
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["location_formatted"];
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["status"];
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      if (value["os_installed"] === true && value["domain_joined"] === true) {
        cell.innerHTML = value["os_installed_formatted"] + "<img class='icon' src='/images/azure-ad-logo.png'></img>";
      } else {
        cell.innerText = value["os_installed_formatted"];
      }
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["battery_charge_formatted"];
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["cpu_temp_formatted"];
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["disk_temp_formatted"];
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["watts_now_formatted"];
      tableBodyRow.appendChild(cell);

      tableBody.appendChild(tableBodyRow);

    });

    remoteOfflineTable.appendChild(tableHeader);
    remoteOfflineTable.appendChild(tableBody);
    oldRemoteOfflineTable.replaceWith(remoteOfflineTable);

  } catch (error) {
    console.error(error);
  }
}


async function updateRemotePresentTable() {

  try {
    const oldRemotePresentTable = document.getElementById('remotePresentTable');
    const remotePresentTable = document.createElement("table");
    remotePresentTable.setAttribute('id', 'remotePresentTable');
    remotePresentTable.style.alignContent = 'left';

    var tableHeader
    var tableHeaderData
    tableHeader = document.createElement("thead");
    tableHeaderData = "";
    tableHeaderData = await fetchData('https://WAN_IP_ADDRESS:31411/api/remote?type=remote_present_header');
    Object.entries(tableHeaderData).forEach(([key, value]) => {
      let tableHeaderRow = document.createElement("tr");

      var cell = document.createElement("th");
      cell.innerText = "Online Clients " + value["tagnumber_count"];
      tableHeaderRow.appendChild(cell);

      var cell = document.createElement("th");
      cell.innerText = "Live View";
      tableHeaderRow.appendChild(cell);

      var cell = document.createElement("th");
      cell.innerText = "Last Job Time";
      tableHeaderRow.appendChild(cell);
      
      var cell = document.createElement("th");
      cell.innerText = "Location";
      tableHeaderRow.appendChild(cell);

      var cell = document.createElement("th");
      cell.innerText = "Status";
      tableHeaderRow.appendChild(cell);

      var cell = document.createElement("th");
      cell.innerText = "OS Installed " + value["os_installed_formatted"];
      tableHeaderRow.appendChild(cell);

      var cell = document.createElement("th");
      cell.innerText = "Battery Charge " + value["battery_charge_formatted"];
      tableHeaderRow.appendChild(cell);

      var cell = document.createElement("th");
      cell.innerText = "Uptime";
      tableHeaderRow.appendChild(cell);

      var cell = document.createElement("th");
      cell.innerText = "CPU Temp " + value["cpu_temp_formatted"];
      tableHeaderRow.appendChild(cell);

      var cell = document.createElement("th");
      cell.innerText = "Disk Temp " + value["disk_temp_formatted"];
      tableHeaderRow.appendChild(cell);

      var cell = document.createElement("th");
      cell.innerText = "Power Usage " + value["power_usage_formatted"];
      tableHeaderRow.appendChild(cell);

      tableHeader.appendChild(tableHeaderRow);
    });



    var tableBody
    var tableBodyData
    tableBody = document.createElement("tbody");
    tableBodyData = await fetchData('https://WAN_IP_ADDRESS:31411/api/remote?type=remote_present');

    Object.entries(tableBodyData).forEach(([key, value]) => {
      let tableBodyRow = document.createElement("tr");

      // Tag Number
      let tagnumber = "";
      if (value["status"] === undefined) {
          tagnumber += "<b>New Entry: </b>";
      } else if (value["status"].length >= 1) {
        if (value["status"] !== "Waiting for job" || value["job_queued"] === true) {
          tagnumber += "<b>In Progress: </b>";
        }
      }

      tagnumber += "<b><a href='tagnumber.php?tagnumber=" + value["tagnumber"] + "' target='_blank'>" + value["tagnumber"] + "</a></b>";

      // if (value["locations_status"] === false) {
        if (value["kernel_updated"] === true && value["bios_updated"] === true) {
          tagnumber += "<span style='color:rgb(0, 120, 50)'><b>&#10004;</b></span>";
        } else if (value["kernel_updated"] === true && value["bios_updated"] !== true) {
          tagnumber += "<span>&#9888;&#65039;</span>";
        } else if (value["kernel_updated"] !== true) {
          tagnumber += "<span>&#10060;</span>";
        }
      // } else {
      //   tagnumber += "<span>🛠️</span>";
      // }
      var cell = document.createElement("td");
      cell.innerHTML = tagnumber;
      tableBodyRow.appendChild(cell);


      var cell = document.createElement("td");
      cell.innerHTML = "<a target='_blank' href='/view-images.php?live_image=1&tagnumber=" + value["tagnumber"] + "'><img style='max-height: 5em;' src='data:image/jpeg;base64," + value["screenshot"] + "'></a>"
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["last_job_time_formatted"];
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["location_formatted"];
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["status"];
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["os_installed_formatted"];
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["battery_charge_formatted"];
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["uptime"];
      tableBodyRow.appendChild(cell);


      // Cpu temp
      var maxTemp
      var lowWarning
      var mediumWarning
      maxTemp = 90;
      lowWarning = maxTemp - (maxTemp * 0.10);
      mediumWarning = maxTemp - (maxTemp * 0.05);
    
      var cell = document.createElement("td");
      cell.innerText = value["cpu_temp_formatted"];
      if (value["cpu_temp"] > lowWarning && value["cpu_temp"] < mediumWarning) {
        cell.style.backgroundColor = '#ffe6a0';
      } else if (value["cpu_temp"] > mediumWarning && value["cpu_temp"] < maxTemp) {
        cell.style.backgroundColor = '#f5aa50';
      } else if (value["cpu_temp"] >= maxTemp) {
        cell.style.backgroundColor = '#f55050';
      }
      tableBodyRow.appendChild(cell);


      // Disk temp
      var maxTemp
      var lowWarning
      var mediumWarning
      maxTemp = value["max_disk_temp"];
      lowWarning = maxTemp - (maxTemp * 0.10);
      mediumWarning = maxTemp - (maxTemp * 0.05);
      var cell = document.createElement("td");
      cell.innerText = value["disk_temp_formatted"];
      if (value["disk_temp"] > lowWarning && value["disk_temp"] < mediumWarning) {
        cell.style.backgroundColor = '#ffe6a0';
      } else if (value["disk_temp"] > mediumWarning && value["disk_temp"] < maxTemp) {
        cell.style.backgroundColor = '#f5aa50';
      } else if (value["disk_temp"] >= maxTemp) {
        cell.style.backgroundColor = '#f55050';
      }
      tableBodyRow.appendChild(cell);

      // Current watts
      var cell = document.createElement("td");
      cell.innerText = value["watts_now"];
      tableBodyRow.appendChild(cell);

      tableBody.appendChild(tableBodyRow)

    });

    remotePresentTable.appendChild(tableHeader);
    remotePresentTable.appendChild(tableBody);
    oldRemotePresentTable.replaceWith(remotePresentTable);
  } catch (error) {
    console.log(error);
  }
}