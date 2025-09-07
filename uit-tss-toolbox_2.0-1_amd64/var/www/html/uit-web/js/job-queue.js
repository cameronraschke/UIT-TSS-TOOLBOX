let isUpdatingPage = false;
let jobQueueUpdateTimeout = null;
let currentAbortController = null;

document.addEventListener('DOMContentLoaded', () => {
    updateJobQueue();
});

async function updateJobQueue() {
    if (currentAbortController) {
        currentAbortController.abort();
    }
    currentAbortController = new AbortController();
    if (isUpdatingPage) return;
    isUpdatingPage = true;
    try {
        await Promise.all([
            updateOnlineTable(currentAbortController.signal),
            updateOfflineTable(currentAbortController.signal)
        ]);
    } catch (error) {
        if (error.name === 'AbortError') {
            console.log('Fetch aborted, another operation is in progress.');
        } else {
            console.error('Error updating job queue:', error);
        }
    } finally {
        currentAbortController = null;
        isUpdatingPage = false;
        jobQueueUpdateTimeout = setTimeout(() => {
            jobQueueUpdateTimeout = null;
            updateJobQueue();
        }, 3000);
    }
}

async function updateOnlineTable(signal) {
  try {
    const [remotePresentHeaderData, remotePresentBodyData] = await Promise.all([
      fetchData('https://UIT_WAN_IP_ADDRESS:31411/api/remote?type=remote_present_header', { signal }),
      fetchData('https://UIT_WAN_IP_ADDRESS:31411/api/remote?type=remote_present', { signal })
    ]);

    const remotePresentTableFragment = new DocumentFragment();
    const remotePresentTable = document.createElement("table");
    remotePresentTable.setAttribute('id', 'remotePresentTable');
    remotePresentTable.style.alignContent = 'left';

    const remotePresentHeaderThead = document.createElement("thead");
    Object.entries(remotePresentHeaderData).forEach(([key, value]) => {
      const tagnumberHeader = document.createElement("th");
      tagnumberHeader.innerText = "Online Clients " + value["tagnumber_count"];

      const screenshotHeader = document.createElement("th");
      screenshotHeader.innerText = "Live View";

      const lastJobTimeHeader = document.createElement("th");
      lastJobTimeHeader.innerText = "Last Job Time";
      
      const locationHeader = document.createElement("th");
      locationHeader.innerText = "Location";

      const statusHeader = document.createElement("th");
      statusHeader.innerText = "Status";

      const osInstalledHeader = document.createElement("th");
      osInstalledHeader.innerText = "OS Installed " + value["os_installed_formatted"];

      const batteryChargeHeader = document.createElement("th");
      batteryChargeHeader.innerText = "Battery Charge " + value["battery_charge_formatted"];

      const uptimeHeader = document.createElement("th");
      uptimeHeader.innerText = "Uptime";

      const cpuTempHeader = document.createElement("th");
      cpuTempHeader.innerText = "CPU Temp " + value["cpu_temp_formatted"];

      const diskTempHeader = document.createElement("th");
      diskTempHeader.innerText = "Disk Temp " + value["disk_temp_formatted"];

      const wattsNowHeader = document.createElement("th");
      wattsNowHeader.innerText = "Power Usage " + value["power_usage_formatted"];

      const remotePresentHeaderTr = document.createElement("tr");
      remotePresentHeaderTr.append(tagnumberHeader, screenshotHeader, lastJobTimeHeader, locationHeader, statusHeader, osInstalledHeader, batteryChargeHeader, uptimeHeader, cpuTempHeader, diskTempHeader, wattsNowHeader)
      remotePresentHeaderThead.append(remotePresentHeaderTr)
    });

    const remotePresentBodyTbody = document.createElement("tbody");
    Object.entries(remotePresentBodyData).forEach(([key, value]) => {
      const remotePresentTableBodyTr = document.createElement("tr");

      // Tag Number
      const beforeTagnumberBodySpan1 = document.createElement("span");
      beforeTagnumberBodySpan1.style.fontWeight = "bold";
      const beforeTagnumberBodyP1 = document.createElement("p");
      let beforeTagnumberBodyText1 = undefined;
      if (value["status"] === undefined || !(value["status"])) {
        beforeTagnumberBodyText1 = document.createTextNode("New Entry: ");
      } else if (value["status"].length >= 1) {
        if (value["job_queued"] && value["job_queued"].length > 0) {
          beforeTagnumberBodyText1 = document.createTextNode("In Progress: ");
        } else if (value["job_queued"] === null && value["status"].match(/(fail.*)/i)) {
          beforeTagnumberBodyText1 = document.createTextNode("Failed: ");
        }
      }

      if (beforeTagnumberBodyText1 !== undefined) {
        beforeTagnumberBodyP1.append(beforeTagnumberBodyText1);
        beforeTagnumberBodySpan1.append(beforeTagnumberBodyP1);
      }
      
      tagnumberBodyA1 = document.createElement("a");
      tagnumberBodyA1.setAttribute("href", "tagnumber.php?tagnumber=" + value["tagnumber"]);
      tagnumberBodyA1.setAttribute("target", "_blank");
      tagnumberBodyA1.style.fontWeight = "bold";
      tagnumberBodyA1.innerText = value["tagnumber"];

      const afterTagnumberBodySpan2 = document.createElement("span");
      let afterTagnumberText2 = "";
      if (value["locations_status"] === true) {
        afterTagnumberText2 += "🛠️"
      }

      if (value["kernel_updated"] === true && value["bios_updated"] === true) {
        afterTagnumberText2 += "✔️";
      } else if (value["kernel_updated"] === true && value["bios_updated"] !== true) {
        afterTagnumberText2 += "⚠️";
      } else if (value["kernel_updated"] !== true) {
        afterTagnumberText2 += "❌";
      }
      afterTagnumberBodySpan2.append(afterTagnumberText2);

      const tagnumberCell = document.createElement("td");
      tagnumberCell.append(beforeTagnumberBodySpan1, tagnumberBodyA1, afterTagnumberBodySpan2);

      // Screenshot cell
      const screenshotCell = document.createElement("td");
      if (value["screenshot"]) {
        const screenshotDataURL = base64ToBlobUrl(value["screenshot"]);
        const screenshotLink = document.createElement("a");
        screenshotLink.setAttribute("target", "_blank");
        screenshotLink.setAttribute("href", "/view-images.php?live_image=1&tagnumber=" + encodeURIComponent(value["tagnumber"]));
        const screenshotImg = document.createElement("img");
        screenshotImg.className = 'fade-in';
        screenshotImg.onload = () => screenshotImg.classList.add('loaded');
        screenshotImg.setAttribute("style", "max-height: 5em; height: 5em;");
        screenshotImg.setAttribute("loading", "lazy");
        screenshotImg.setAttribute("src", screenshotDataURL);
        screenshotLink.appendChild(screenshotImg);
        screenshotCell.appendChild(screenshotLink);
      }

      // Last job time cell
      const lastJobTimeCell = document.createElement("td");
      lastJobTimeCell.innerText = value["last_job_time_formatted"];

      // Location cell
      const locationCell = document.createElement("td");
      const locationLink = document.createElement("a");
      locationLink.style.fontWeight = "bold";
      locationLink.setAttribute('href', '/locations.php?location=' + encodeURIComponent(value["location_formatted"]));
      locationLink.textContent = value["location_formatted"];
      locationCell.append(locationLink);

      // Status cell (working/broken)
      const statusCell = document.createElement("td");
      statusCell.innerText = value["status"];

      // OS installed
      const osInstalledCell = document.createElement("td");
      if (value["os_installed"] === true && value["domain_joined"] === true) {
        osInstalledCell.innerHTML = value["os_installed_formatted"] + "<img class='icon' src='/images/intune-joined.svg'></img>";
      } else {
        osInstalledCell.innerText = value["os_installed_formatted"];
      }

      // Battery charge
      const batteryChargeCell = document.createElement("td");
      batteryChargeCell.innerText = value["battery_charge_formatted"];

      const uptimeCell = document.createElement("td");
      uptimeCell.innerText = value["uptime"];

      // Cpu temp
      const cpuMaxTemp = 90;
      const cpuLowWarning = cpuMaxTemp - (cpuMaxTemp * 0.10);
      const cpuMediumWarning = cpuMaxTemp - (cpuMaxTemp * 0.05);
    
      let cpuTempCell = document.createElement("td");
      cpuTempCell.innerText = value["cpu_temp_formatted"];
      if (value["cpu_temp"] > cpuLowWarning && value["cpu_temp"] < cpuMediumWarning) {
        cpuTempCell.style.backgroundColor = '#ffe6a0';
      } else if (value["cpu_temp"] > cpuMediumWarning && value["cpu_temp"] < cpuMaxTemp) {
        cpuTempCell.style.backgroundColor = '#f5aa50';
      } else if (value["cpu_temp"] >= cpuMaxTemp) {
        cpuTempCell.style.backgroundColor = '#f55050';
      }

      // Disk temp
      const diskMaxTemp = value["max_disk_temp"];
      const diskLowWarning = diskMaxTemp - (diskMaxTemp * 0.10);
      const diskMediumWarning = diskMaxTemp - (diskMaxTemp * 0.05);
      let diskTempCell = document.createElement("td");
      diskTempCell.innerText = value["disk_temp_formatted"];
      if (value["disk_temp"] > diskLowWarning && value["disk_temp"] < diskMediumWarning) {
        diskTempCell.style.backgroundColor = '#ffe6a0';
      } else if (value["disk_temp"] > diskMediumWarning && value["disk_temp"] < diskMaxTemp) {
        diskTempCell.style.backgroundColor = '#f5aa50';
      } else if (value["disk_temp"] >= diskMaxTemp) {
        diskTempCell.style.backgroundColor = '#f55050';
      }

      // Current watts
      let wattsNowCell = document.createElement("td");
      wattsNowCell.innerText = value["watts_now"];

      remotePresentTableBodyTr.append(tagnumberCell, screenshotCell, lastJobTimeCell, locationCell, statusCell, osInstalledCell, batteryChargeCell, uptimeCell, cpuTempCell, diskTempCell, wattsNowCell)
      remotePresentBodyTbody.append(remotePresentTableBodyTr)
    });

    remotePresentTable.append(remotePresentHeaderThead, remotePresentBodyTbody);
    remotePresentTableFragment.append(remotePresentTable);
    const oldRemotePresentTable = document.getElementById("remotePresentTable");
    oldRemotePresentTable.classList.add("fade-in");
    setTimeout(() => {
        oldRemotePresentTable.replaceWith(remotePresentTableFragment);
        const newTable = document.getElementById("remotePresentTable");
        newTable.classList.add("fade-in");
        setTimeout(() => newTable.classList.add("show"), 10);
    }, 300);
    oldRemotePresentTable.replaceWith(remotePresentTableFragment);
  } catch (error) {
    console.log(error);
  }
}

async function updateOfflineTable(signal) {
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
    tableBodyData = await fetchData('https://UIT_WAN_IP_ADDRESS:31411/api/remote?type=remote_offline', { signal });

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
