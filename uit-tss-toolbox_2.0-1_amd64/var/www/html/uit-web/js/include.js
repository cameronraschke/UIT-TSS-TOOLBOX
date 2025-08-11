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
      cell.innerHTML = "<b><a href='tagnumber.php?tagnumber=" + value["tagnumber"] + "' target='_blank'>" + value["tagnumber"] + "</a></b>";

      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["time_formatted"];
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      var link = document.createElement("a");
      link.style.fontWeight = "bold";
      link.setAttribute('href', '/locations.php?location=' + encodeURIComponent(value["location_formatted"]));
      link.textContent = value["location_formatted"];
      cell.appendChild(link);
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      cell.innerText = value["status"];
      tableBodyRow.appendChild(cell);

      var cell = document.createElement("td");
      if (value["os_installed"] === true && value["domain_joined"] === true) {
        cell.innerHTML = value["os_installed_formatted"] + "<img class='icon' src='/images/intune-joined.svg'></img>";
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

      let tagnumberCell = document.createElement("th");
      tagnumberCell.innerText = "Online Clients " + value["tagnumber_count"];
      tableHeaderRow.appendChild(tagnumberCell);

      let screenshotCell = document.createElement("th");
      screenshotCell.innerText = "Live View";
      tableHeaderRow.appendChild(screenshotCell);

      let lastJobTimeCell = document.createElement("th");
      lastJobTimeCell.innerText = "Last Job Time";
      tableHeaderRow.appendChild(lastJobTimeCell);
      
      let locationCell = document.createElement("th");
      locationCell.innerText = "Location";
      tableHeaderRow.appendChild(locationCell);

      let statusCell = document.createElement("th");
      statusCell.innerText = "Status";
      tableHeaderRow.appendChild(statusCell);

      let osInstalledCell = document.createElement("th");
      osInstalledCell.innerText = "OS Installed " + value["os_installed_formatted"];
      tableHeaderRow.appendChild(osInstalledCell);

      let batteryChargeCell = document.createElement("th");
      batteryChargeCell.innerText = "Battery Charge " + value["battery_charge_formatted"];
      tableHeaderRow.appendChild(batteryChargeCell);

      let uptimeCell = document.createElement("th");
      uptimeCell.innerText = "Uptime";
      tableHeaderRow.appendChild(uptimeCell);

      let cpuTempCell = document.createElement("th");
      cpuTempCell.innerText = "CPU Temp " + value["cpu_temp_formatted"];
      tableHeaderRow.appendChild(cpuTempCell);

      let diskTempCell = document.createElement("th");
      diskTempCell.innerText = "Disk Temp " + value["disk_temp_formatted"];
      tableHeaderRow.appendChild(diskTempCell);

      let wattsNowCell = document.createElement("th");
      wattsNowCell.innerText = "Power Usage " + value["power_usage_formatted"];
      tableHeaderRow.appendChild(wattsNowCell);

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
      if (value["status"] === undefined || !(value["status"])) {
          tagnumber += "<b>New Entry: </b>";
      } else if (value["status"].length >= 1) {
        if (value["status"] !== "Waiting for job" || value["job_queued"] === true) {
          tagnumber += "<b>In Progress: </b>";
        }
      }
      

      tagnumber += "<b><a href='tagnumber.php?tagnumber=" + value["tagnumber"] + "' target='_blank'>" + value["tagnumber"] + "</a></b>";
      if (value["locations_status"] === true) {
        tagnumber += "🛠️"
      }

      if (value["kernel_updated"] === true && value["bios_updated"] === true) {
        tagnumber += "<span style='color:rgb(0, 120, 50)'><b>&#10004;</b></span>";
      } else if (value["kernel_updated"] === true && value["bios_updated"] !== true) {
        tagnumber += "<span>&#9888;&#65039;</span>";
      } else if (value["kernel_updated"] !== true) {
        tagnumber += "<span>&#10060;</span>";
      }
      let tagnumberCell = document.createElement("td");
      tagnumberCell.innerHTML = tagnumber;
      tableBodyRow.appendChild(tagnumberCell);


      let screenshotCell = document.createElement("td");
      if (value["screenshot"]) {
        screenshotCell.innerHTML = "<a target='_blank' href='/view-images.php?live_image=1&tagnumber=" + value["tagnumber"] + "'><img style='max-height: 5em;' src='data:image/jpeg;base64," + value["screenshot"] + "'></a>"
      }
      tableBodyRow.appendChild(screenshotCell);

      let lastJobTimeCell = document.createElement("td");
      lastJobTimeCell.innerText = value["last_job_time_formatted"];
      tableBodyRow.appendChild(lastJobTimeCell);

      let locationCell = document.createElement("td");
      let link = document.createElement("a");
      link.style.fontWeight = "bold";
      link.setAttribute('href', '/locations.php?location=' + encodeURIComponent(value["location_formatted"]));
      link.textContent = value["location_formatted"];
      locationCell.appendChild(link);
      tableBodyRow.appendChild(locationCell);

      let statusCell = document.createElement("td");
      statusCell.innerText = value["status"];
      tableBodyRow.appendChild(statusCell);

      let osInstalledCell = document.createElement("td");
      osInstalledCell.innerText = value["os_installed_formatted"];
      tableBodyRow.appendChild(osInstalledCell);

      let batteryChargeCell = document.createElement("td");
      batteryChargeCell.innerText = value["battery_charge_formatted"];
      tableBodyRow.appendChild(batteryChargeCell);

      let uptimeCell = document.createElement("td");
      uptimeCell.innerText = value["uptime"];
      tableBodyRow.appendChild(uptimeCell);


      // Cpu temp
      let cpuMaxTemp
      let cpuLowWarning
      let cpuMediumWarning
      cpuMaxTemp = 90;
      cpuLowWarning = cpuMaxTemp - (cpuMaxTemp * 0.10);
      cpuMediumWarning = cpuMaxTemp - (cpuMaxTemp * 0.05);
    
      let cpuTempCell = document.createElement("td");
      cpuTempCell.innerText = value["cpu_temp_formatted"];
      if (value["cpu_temp"] > cpuLowWarning && value["cpu_temp"] < cpuMediumWarning) {
        cpuTempCell.style.backgroundColor = '#ffe6a0';
      } else if (value["cpu_temp"] > cpuMediumWarning && value["cpu_temp"] < cpuMaxTemp) {
        cpuTempCell.style.backgroundColor = '#f5aa50';
      } else if (value["cpu_temp"] >= cpuMaxTemp) {
        cpuTempCell.style.backgroundColor = '#f55050';
      }
      tableBodyRow.appendChild(cpuTempCell);


      // Disk temp
      let diskMaxTemp
      let diskLowWarning
      let diskMediumWarning
      diskMaxTemp = value["max_disk_temp"];
      diskLowWarning = diskMaxTemp - (diskMaxTemp * 0.10);
      diskMediumWarning = diskMaxTemp - (diskMaxTemp * 0.05);
      let diskTempCell = document.createElement("td");
      diskTempCell.innerText = value["disk_temp_formatted"];
      if (value["disk_temp"] > diskLowWarning && value["disk_temp"] < diskMediumWarning) {
        diskTempCell.style.backgroundColor = '#ffe6a0';
      } else if (value["disk_temp"] > diskMediumWarning && value["disk_temp"] < diskMaxTemp) {
        diskTempCell.style.backgroundColor = '#f5aa50';
      } else if (value["disk_temp"] >= diskMaxTemp) {
        diskTempCell.style.backgroundColor = '#f55050';
      }
      tableBodyRow.appendChild(diskTempCell);

      // Current watts
      let wattsNowCell = document.createElement("td");
      wattsNowCell.innerText = value["watts_now"];
      tableBodyRow.appendChild(wattsNowCell);

      tableBody.appendChild(tableBodyRow)

    });

    remotePresentTable.appendChild(tableHeader);
    remotePresentTable.appendChild(tableBody);
    oldRemotePresentTable.replaceWith(remotePresentTable);
  } catch (error) {
    console.log(error);
  }
}


async function autoFillTags() {
  try {
    tagJson = await fetchData('https://WAN_IP_ADDRESS:31411/api/locations?type=all_tags');

    let tagStr = []
    Object.entries(tagJson).forEach(([key, value]) => {
      tagStr.push(String(value["tagnumber"]));
    });

    document.getElementById('dropdown-search').style.display = "none";
    document.getElementById('dropdown-search').innerHTML = "";

    var tagnumberField = document.getElementById('tagnumber-search');

    document.querySelector('body').addEventListener('click', () => {
      if (document.activeElement !== document.getElementById('tagnumber-search') && document.activeElement !== document.getElementById('tagnumber-search')) {
          document.getElementById('dropdown-search').style.display = "none";
          document.getElementById('dropdown-search').innerHTML = "";
          tagnumberField.value = "";
      }
    });

    tagnumberField.addEventListener('keyup', (event) => {
      if (event.key === 'Backspace' || event.key === 'Delete' || event.key == "Escape") {
        tagnumberField.value = tagnumberField.value.substr(0, getCursorPos(tagnumberField));
        tagnumberField.setSelectionRange(getCursorPos(tagnumberField), getCursorPos(tagnumberField));
      }
    });

    tagnumberField.addEventListener('input', function() {
      const inputText = tagnumberField.value;
      var re = new RegExp('^' + inputText, 'gi');
      var re1 = new RegExp('^' + inputText + '$', 'gi');
      const matchingExact = tagStr.find(suggestion => suggestion.match(re1));
      const matchingSuggestion = tagStr.find(suggestion => suggestion.match(re));
      const allMatches = tagStr.filter((suggestionList) => suggestionList.match(re));

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
    });
  } catch (error) {
    console.error(error);
  }
}
