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


async function updateTagnumberData(tagnumber) {
  try {
    const oldGeneralClientInfo = document.getElementById("client_info");
    const oldDiskInfo = document.getElementById("disk_info");
    const oldClientHealthInfo = document.getElementById("client_health");
    const oldCpuRamInfo = document.getElementById("cpu_ram_info");

    const generalClientInfoFragment = new DocumentFragment();
    
    const diskInfo = document.createElement("table");
    diskInfo.setAttribute("id", "disk_info");

    const clientHealthInfo = document.createElement("table");
    clientHealthInfo.setAttribute("id", "client_health");

    const cpuRamInfo = document.createElement("table");
    cpuRamInfo.setAttribute("id", "cpu_ram_info");

    const tagnumberData = await fetchData("https://WAN_IP_ADDRESS:31411/api/remote?type=tagnumber_data&tagnumber=" + encodeURIComponent(tagnumber));
    Object.entries(tagnumberData).forEach(([key, value]) => {
      const newTabImg = document.createElement("img");
      newTabImg.classList.add('icon');
      newTabImg.src = '/images/new-tab.svg';

      // table
      const generalClientInfoTable = document.createElement("table");
      generalClientInfoTable.setAttribute("id", "client_info");
      generalClientInfoTable.style.width = '100%';

      // thead
      const generalClientInfoHeadThead = document.createElement("thead");
      generalClientInfoTable.append(generalClientInfoHeadThead);

      // tr
      const generalClientInfoHeadTR = document.createElement("tr");
      generalClientInfoHeadThead.appendChild(generalClientInfoHeadTR);

      // th - extra th for formatting
      const generalClientInfoHeadTH = document.createElement("th");
      generalClientInfoHeadTH.innerText = "General Client Info - " + value["tagnumber"];
      generalClientInfoHeadTR.appendChild(generalClientInfoHeadTH);
      generalClientInfoHeadTR.appendChild(document.createElement("th"));

      // tbody
      const generalClientBodyTbody = document.createElement("tbody");
      generalClientInfoTable.append(generalClientBodyTbody);

      // location data
      const location = document.createTextNode(value["location"]);
      const locationReadable = document.createTextNode('"' + value["location"] + '" ');
      const locationRow = document.createElement("tr");

      const locationTD1 = document.createElement("td");
      locationTD1.innerText = "Current Location";

      const locationTD2 = document.createElement("td");
      if (value["locations_status"] && value["locations_status"] == true) {
        const locationErrorSpan = document.createElement("span");
        locationErrorSpan.style.whiteSpace = "nowrap";
        const locationErrorP1 = document.createElement("p");
        locationErrorP1.setAttribute("class", "error");
        locationErrorP1.innerText = "[REPORTED BROKEN] ";
        const locationErrorP2 = document.createElement("p");
        locationErrorP2.style.fontWeight = "bold";
        locationErrorP2.innerText = "on " + value["location_time_formatted"];
        locationErrorSpan.append(locationErrorP1, locationErrorP2);
        locationTD2.append(locationErrorSpan);
      }
      if (value["checkout_bool"] && value["checkout_bool"] == true) {
        const checkoutP = document.createElement("p");
        const checkoutSpan1 = document.createElement("span");
        checkoutSpan1.style.fontWeight = "bold";
        const checkoutText1 = document.createTextNode("[CHECKOUT] ");
        checkoutSpan1.append(checkoutText1);
        const checkoutSpan2 = document.createElement("span");
        const checkoutText2 = document.createTextNode("- Checked out to ");
        checkoutSpan2.append(checkoutText2);
        const checkoutSpan3 = document.createElement("span");
        checkoutSpan3.style.fontWeight = "bold";
        const checkoutText3 = document.createTextNode(value["customer_name"]);
        checkoutSpan3.append(checkoutText3);
        const checkoutSpan4 = document.createElement("span");
        const checkoutText4 = document.createTextNode(" on ");
        checkoutSpan4.append(checkoutText4);
        const checkoutSpan5 = document.createElement("span");
        checkoutSpan5.style.fontWeight = "bold";
        const checkoutText5 = document.createTextNode(value["checkout_date"]);
        checkoutSpan5.append(checkoutText5);
        checkoutP.append(checkoutSpan1, checkoutSpan2, checkoutSpan3, checkoutSpan4, checkoutSpan5);
        locationTD2.append(checkoutP);
      }

      const locationP = document.createElement("p");
      locationP.style.marginBottom = "1em";
      locationP.style.marginTop = "1em";
      locationP.append(locationReadable);
      locationTD2.append(locationP);


      if (value["note"] && value["note"].length > 0) {
        const noteP = document.createElement("p");
        noteP.style.marginBottom = "1em";
        const noteSpan1 = document.createElement("span");
        noteSpan1.style.fontWeight = "bold";
        const noteText1 = document.createTextNode("Note: ");
        noteSpan1.append(noteText1);
        const noteSpan2 = document.createElement("span");
        const noteText2 = document.createTextNode(value["note"]);
        noteSpan2.append(noteText2);
        noteP.append(noteSpan1, noteSpan2);
        locationTD2.append(noteP);
      }

      locationRow.append(locationTD1, locationTD2);

      // Department cell
      const departmentRow = document.createElement("tr");
      const departmentTD1 = document.createElement("td");
      const departmentTD2 = document.createElement("td");

      const departmentReadable = document.createTextNode(value["department_readable"] + " ");

      departmentTD1.innerText = "Department";

      const departmentP1 = document.createElement("p");

      const departmentA1 = document.createElement("a");
      departmentA1.style.cursor = "pointer";
      departmentA1.style.fontStyle = "italic";
      departmentA1.setAttribute("onclick", "newLocationWindow('" + encodeURIComponent(value["location"]) + "', '" + encodeURIComponent(value["tagnumber"]) + "', '" + encodeURIComponent(value["department"]) + "')");
      departmentA1.innerText = "(Click to Update Department)";

      departmentA1.append(newTabImg.cloneNode(true));

      departmentP1.append(departmentReadable, departmentA1);
      departmentTD2.append(departmentP1);      
      
      departmentRow.append(departmentTD1, departmentTD2);
      
      // Domain cell
      const domainRow = document.createElement("tr");
      const domainTD1 = document.createElement("td");
      const domainTD2 = document.createElement("td");

      const domainReadable = document.createTextNode(value["domain_readable"] + " ");

      domainTD1.innerText = "Domain";

      const domainP1 = document.createElement("p");

      const domainA1 = document.createElement("a");
      domainA1.style.cursor = "pointer";
      domainA1.style.fontStyle = "italic";
      domainA1.setAttribute("onclick", "newLocationWindow('" + encodeURIComponent(value["location"]) + "', '" + encodeURIComponent(value["tagnumber"]) + "', '" + encodeURIComponent(value["domain"]) + "')");
      domainA1.innerText = "(Click to Update Domain)";
      
      domainA1.append(newTabImg.cloneNode(true));

      domainP1.append(domainReadable, domainA1);
      domainTD2.append(domainP1);      
      
      domainRow.append(domainTD1, domainTD2);


      // system_serial cell
      const serialRow = document.createElement("tr");
      const serialTD1 = document.createElement("td");
      const serialTD2 = document.createElement("td");

      const serial = document.createTextNode(value["system_serial"]);

      serialTD1.innerText = "Serial Number";

      const serialP1 = document.createElement("p");
      serialP1.append(serial);

      serialTD2.append(serialP1);

      serialRow.append(serialTD1, serialTD2);

      
      // mac addresses
      const macRow = document.createElement("tr");
      const macTD1 = document.createElement("td");
      const macTD2 = document.createElement("td");

      let wifiMacFormatted = undefined;
      let etherAddressFormatted = undefined;

      macTD1.innerText = "MAC Address";

      if (value["wifi_mac_formatted"] && value["wifi_mac_formatted"].length > 0) {
        wifiMacFormatted = document.createTextNode(value["wifi_mac_formatted"] + " (Wi-Fi)");
      }

      if (value["etheraddress_formatted"] && value["etheraddress_formatted"].length > 0) {
        if (value["network_speed_formatted"] && value["network_speed_formatted"].length > 0) {
          etherAddressFormatted = document.createTextNode(value["etheraddress_formatted"] + " (Ethernet) (" + value["network_speed_formatted"] + ")");
        } else {
          etherAddressFormatted = document.createTextNode(value["etheraddress_formatted"] + " (Ethernet)");
        }
      }


      if (wifiMacFormatted !== undefined && etherAddressFormatted !== undefined) {
        const macTable = document.createElement("table");
        macTable.style.maxWidth = "fit-content";
        macTable.style.marginLeft = "0";
        const macTBody = document.createElement("tbody");

        const macTR1 = document.createElement("tr");
        macTR1.style.borderBottom = "1px solid black";
        const macSubTD1 = document.createElement("td");
        const macP1 = document.createElement("p");
        macP1.append(wifiMacFormatted);
        macSubTD1.append(macP1);
        macTR1.append(macSubTD1);

        const macTR2 = document.createElement("tr");
        macTR2.style.borderBottom = "none";
        const macSubTD2 = document.createElement("td");
        const macP2 = document.createElement("p");
        macP2.append(etherAddressFormatted);
        macSubTD2.append(macP2);
        macTR2.append(macSubTD2);

        macTBody.append(macTR1, macTR2);
        macTable.append(macTBody);
        macTD2.append(macTable);
      } else if (wifiMacFormatted !== undefined && etherAddressFormatted === undefined) {
        const macP1 = document.createElement("p");
        macP1.append(wifiMacFormatted);
        macTD2.append(macP1);
      } else if (wifiMacFormatted === undefined && etherAddressFormatted !== undefined) {
        const macP1 = document.createElement("p");
        macP1.append(etherAddressFormatted);
        macTD2.append(macP1);
      }

      macRow.append(macTD1, macTD2);
      

      // System model
      const modelRow = document.createElement("tr");
      const modelTD1 = document.createElement("td");
      const modelTD2 = document.createElement("td");

      modelTD1.innerText = "System Model";

      const modelP1 = document.createElement("p");
      const modelText1 = document.createTextNode(value["system_model_formatted"]);

      modelP1.append(modelText1);
      modelTD2.append(modelP1);
      modelRow.append(modelTD1, modelTD2);


      // OS Version
      const osRow = document.createElement("tr");
      const osTD1 = document.createElement("td");
      const osTD2 = document.createElement("td");

      osTD1.innerText = "OS Version";

      const osP1 = document.createElement("p");
      let osText1 = undefined;
      // Don't check for tpm length bc it comes back from db as an int
      if (value["tpm_version"]) {
        osText1 = document.createTextNode(value["os_installed_formatted"] + " (TPM v" + value["tpm_version"] + ")");
      } else {
        osText1 = document.createTextNode(value["os_installed_formatted"]);
      }
      
      osP1.append(osText1);
      osTD2.append(osP1);
      osRow.append(osTD1, osTD2);


      
      // Bitlocker info
      const bitlockerRow = document.createElement("tr");
      const bitlockerTD1 = document.createElement("td");
      const bitlockerTD2 = document.createElement("td");

      bitlockerTD1.innerText = "Bitlocker Info";

      if (value["recovery_key"] && value["recovery_key"].length > 0 && value["identifier"] && value["identifier"].length > 0) {
        const bitlockerTable = document.createElement("table");

        const bitlockerTR1 = document.createElement("tr");
        const bitlockerSubTD1 = document.createElement("td");
        const bitlockerP1 = document.createElement("p");
        const bitlockerText1 = document.createTextNode(value["identifier"]);
        bitlockerP1.append(bitlockerText1);
        bitlockerSubTD1.append(bitlockerP1);
        bitlockerTR1.append(bitlockerSubTD1);

        const bitlockerTR2 = document.createElement("tr");
        const bitlockerSubTD2 = document.createElement("td");
        const bitlockerP2 = document.createElement("p");
        const bitlockerText2 = document.createTextNode(value["recovery_key"]);
        bitlockerP2.append(bitlockerText2);
        bitlockerSubTD2.append(bitlockerP2);
        bitlockerTR2.append(bitlockerSubTD2);

        bitlockerTable.append(bitlockerTR1, bitlockerTR2);
        bitlockerTD2.append(bitlockerTable);
      }

      bitlockerRow.append(bitlockerTD1, bitlockerTD2);


      // bios_version
      const biosRow = document.createElement("tr");
      const biosTD1 = document.createElement("td");
      const biosTD2 = document.createElement("td");

      biosTD1.innerText = "BIOS Info";

      const biosP2 = document.createElement("p");

      let biosText2 = undefined;
      if (value["bios_updated_formatted"] && value["bios_updated_formatted"].length > 0) {
        biosText2 = document.createTextNode(value["bios_updated_formatted"]);
      } else { 
        biosText2 = document.createTextNode("");
      }

      biosP2.append(biosText2);
      biosTD2.append(biosP2);

      biosRow.append(biosTD1, biosTD2);


      generalClientBodyTbody.append(locationRow, departmentRow, domainRow, serialRow, macRow, modelRow, osRow, bitlockerRow, biosRow);
      generalClientInfoFragment.replaceChildren(generalClientInfoTable);
    });

    oldGeneralClientInfo.replaceChildren(generalClientInfoFragment);

  } catch(error) {
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

      var cell = document.createElement("td");
      if (value["os_installed"] === true && value["domain_joined"] === true) {
        cell.innerHTML = value["os_installed_formatted"] + "<img class='icon' src='/images/intune-joined.svg'></img>";
      } else {
        cell.innerText = value["os_installed_formatted"];
      }
      tableBodyRow.appendChild(cell);

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
      if (event.key === 'Backspace' || event.key === 'Delete') {
        tagnumberField.value = tagnumberField.value.substr(0, getCursorPos(tagnumberField));
        tagnumberField.setSelectionRange(getCursorPos(tagnumberField), getCursorPos(tagnumberField));
      } else if (event.key == "Escape") {
        document.getElementById('dropdown-search').style.display = "none";
        document.getElementById('dropdown-search').innerHTML = "";
        tagnumberField.value = "";
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
