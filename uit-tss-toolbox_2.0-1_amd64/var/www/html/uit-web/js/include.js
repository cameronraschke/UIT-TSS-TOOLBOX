// Functions are used after the DOM is loaded
let isLoggingOut = false;

const authChannel = new BroadcastChannel('auth');
authChannel.onmessage = function(event) {
  console.log(event);
  if (event.data.cmd === 'logout') {
    logout();
  }
};

const logoutButton = document.querySelector('#logout');
logoutButton.addEventListener('click', e => {
  console.log('Logout...');
  e.preventDefault();
  logoutButton.disabled = true;
  authChannel.postMessage({cmd: 'logout'});
  logout();
});

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

function scrollToTop() {
  window.scrollTo(0, 0);
}

function getCursorPos(myElement) {
  let startPosition = myElement.selectionStart;
  let endPosition = myElement.selectionEnd;

  myElement.focus();
  return(startPosition);
}

async function logout() {
  if (isLoggingOut) return;
  isLoggingOut = true;

  const tokenDB = indexedDB.open("uitTokens", 1);
  tokenDB.onsuccess = async function(event) {
    const db = event.target.result;
    const tokenTransaction = db.transaction(["uitTokens"], "readwrite");
    const tokenObjectStore = tokenTransaction.objectStore("uitTokens");
    const basicToken = await getKeyFromIndexDB("basicToken");
    await invalidateSession(basicToken);

    const tokenClearRequest = tokenObjectStore.clear();
    tokenClearRequest.onsuccess = function() {
      console.log("All auth sessions removed");
    };
    tokenClearRequest.onerror = function(event) {
      console.error("Cannot clear previous auth sessions: " + event.target.errorCode);
    };
    db.close();

    deleteCookie("csrf_token", "/", null);
    deleteCookie("PHPSESSID", "/", null);
    localStorage.clear();
    sessionStorage.clear();
    window.location.replace("/logout.php");

  };
  tokenDB.onerror = function(event) {
    console.error("TokenDB error while attempting to purge old sessions: " + event.target.errorCode);
  };
}

async function updateDynamicTagnumberJobData(tagnumber) {
  try {
    const [jobQueueByTagData, availableJobs] = await Promise.all([
      fetchData('https://UIT_WAN_IP_ADDRESS:31411/api/remote?type=job_queue_by_tag&tagnumber=' + encodeURIComponent(tagnumber).replace(/'/g, "%27")),
      fetchData('https://UIT_WAN_IP_ADDRESS:31411/api/remote?type=available_jobs&tagnumber=' + encodeURIComponent(tagnumber).replace(/'/g, "%27"))
    ]);


    if (jobQueueByTagData && availableJobs && Object.keys(jobQueueByTagData).length > 0) {
      const clientStatus = document.getElementById("client_status");
      const formButton = document.getElementById("job_form_button");
      const jobSelect = document.getElementById("job_queued_select");
      const newSelect = document.createElement("select");
      newSelect.setAttribute("id", "job_queued_select");
      newSelect.setAttribute("name", "job_queued_select");
      updateLiveImage(tagnumber);
      Object.entries(jobQueueByTagData).forEach(([key, value]) => {
        let clientStatusTextFormatted = undefined
        if (value["tagnumber"] && value["tagnumber"] > 0) {
          const clientStatusP1 = document.createElement("p");
          // BIOS and kernel updated (check mark)
          if (value["present_bool"] === true && (value["kernel_updated"] === true && value["bios_updated"] === true)) {
            clientStatusTextFormatted = "Online, no errors ✔️";
          // BIOS and kernel out of date (x)
          } else if (value["present_bool"] === true && (value["kernel_updated"] !== true && value["bios_updated"] !== true)) {
            clientStatusTextFormatted = "Online, kernel and BIOS out of date ❌";
          // BIOS out of date, kernel updated (warning sign)
          } else if (value["present_bool"] === true && (value["kernel_updated"] === true && value["bios_updated"] !== true)) {
            clientStatusTextFormatted = "Online, please update BIOS ⚠️";
          // BIOS updated, kernel out of date (x)
          } else if (value["present_bool"] === true && (value["kernel_updated"] !== true && value["bios_updated"] === true)) {
            clientStatusTextFormatted = "Online, kernel out of date ❌";
          // Offline (x)
          } else if (value["present_bool"] !== true) {
            clientStatusTextFormatted = "Offline ⛔";
          } else {
            clientStatusTextFormatted = "Unknown ⛔";
          }

          const clientStatusText1 = "Real-time Job Status for " + value["tagnumber"] + ": ";
          clientStatusP1.replaceChildren(clientStatusText1);
          clientStatus.replaceChildren(clientStatusP1);
          document.getElementById("queue_status").innerText = clientStatusTextFormatted;
        } else {
          const clientStatusP1 = document.createElement("p");
          const clientStatusText1 = document.createTextNode("Missing required info. Please plug into laptop server to gather information.");
          const clientStatusP2 = document.createElement("p");
          const clientStatusText2 = document.createTextNode("To update the location, please update it from the ");
          const clientStatusA1 = document.createElement("a");
          clientStatusA1.href = "/locations.php?edit=1&tagnumber=" + encodeURIComponent(tagnumber).replace(/'/g, "%27");
          clientStatusA1.innerText = "locations page";
          
          clientStatusP1.append(clientStatusText1);
          clientStatusP2.append(clientStatusText2);
          clientStatusP2.append(clientStatusA1);
          clientStatus.replaceChildren(clientStatusP1, clientStatusP2);
        }

        const jobQueuedTagnumber = document.getElementById("job_queued_tagnumber");
        jobQueuedTagnumber.value = value["tagnumber"];

        const firstOption = document.createElement('option');
        if (value["job_active"] || (value["job_queued"] && value["job_queued"].length > 1 && value["job_queued"] !== "cancel")) {
          firstOption.textContent = "In Progress: " + value["job_queued_formatted"];
          if (value["job_queued"] !== "null") {
            firstOption.value = value["job_queued"];
          } else {
            firstOption.value = "";
          }
          firstOption.selected = true;
          newSelect.setAttribute("disabled", "true");
        } else {
          firstOption.textContent = "--Select Job Below--";
          firstOption.value = "";
          firstOption.selected = true;
          newSelect.removeAttribute("disabled");
        } 

        newSelect.replaceChildren(firstOption);

        Object.entries(availableJobs).forEach(([key1, value1]) => {
          const option = document.createElement('option');
          option.value = value1["job"];
          option.textContent = value1["job_readable"];
          option.selected = false;
          newSelect.append(option);
        });

        // check if new option values are different than current option values
        if (document.getElementById("job_queued_select").options[0].textContent !== newSelect.options[0].textContent) {
          jobSelect.replaceWith(newSelect);
        }
        if (value["job_active"] || (value["job_queued"] && value["job_queued"].length > 1 && value["job_queued"] !== "cancel" && value["job_queued"] !== "null")) {
          formButton.innerText = "Cancel Job";
          formButton.removeAttribute("onclick");
          formButton.setAttribute("onclick", "ConfirmCancelJob('" + encodeURIComponent(tagnumber).replace(/'/g, "%27") + "')");
          formButton.style.backgroundColor = "";
          formButton.style.backgroundColor = "rgba(200, 16, 47, 0.31)";
          formButton.removeAttribute("disabled");
          jobSelect.setAttribute("disabled", "true");
        } else {
          formButton.innerText = "Queue Job";
          formButton.removeAttribute("onclick");
          formButton.style.backgroundColor = "";
          formButton.style.backgroundColor = "rgba(0, 179, 136, 0.30);";
          formButton.removeAttribute("disabled");
          jobSelect.removeAttribute("disabled");
        }
      });
    } else {
      console.log("No job queue data or available jobs data");
    }
  } catch(error) {
    console.error(error);
  }
}

async function ConfirmCancelJob(tagnumber) {
  var cancelJob = confirm("Are you sure you want to cancel this job?");
  if (cancelJob === true) {
    document.querySelector("#job_queued_select").value = "cancel";
    const jsonObj = {
      "job_queued_tagnumber": tagnumber,
      "job_queued_select": "cancel" 
    };
    const jsonStr = JSON.stringify(jsonObj);
    await postData("job_queued", jsonStr);
    return true;
  } else {
    return false;
  }
}

async function QueueJobFormatting() {
  const formButton = document.getElementById("job_form_button");
  formButton.innerText = "Queuing...";
  formButton.setAttribute("disabled", "true");
  formButton.style.backgroundColor = "";
}

async function updateStaticTagnumberData(tagnumber) {
  try {
    const [jobQueueByTagData, availableJobs, liveImage] = await Promise.all([
      fetchData('https://UIT_WAN_IP_ADDRESS:31411/api/remote?type=job_queue_by_tag&tagnumber=' + encodeURIComponent(tagnumber).replace(/'/g, "%27")),
      fetchData('https://UIT_WAN_IP_ADDRESS:31411/api/remote?type=available_jobs&tagnumber=' + encodeURIComponent(tagnumber).replace(/'/g, "%27"))
    ]);

    const oldJobQueueSection = document.getElementById("job_queued");
    const jobQueueSectionFragment = new DocumentFragment();

    Object.entries(jobQueueByTagData).forEach(([key, value]) => {
      const parentDiv = document.createElement("div");
      parentDiv.classList.add("flex-container");
      parentDiv.classList.add("location-form");

      // col 1
      const col1 = document.createElement("div");
      col1.setAttribute("id", "job_queue_div");
      col1.classList.add("flex-container-child");
      const jobStatus = document.createElement("div");


      const jobFormParentDiv = document.createElement("div");
      const jobForm = document.createElement("form");
      jobForm.setAttribute("id", "job_queued_form");
      const jobFormDiv1 = document.createElement("div");
      jobFormDiv1.style.paddingLeft = "0";
      const jobFormLabel1 = document.createElement("label");
      jobFormLabel1.setAttribute("for", "job_queued_tagnumber")
      jobFormLabel1.innerText = "Enter a job to queue: "
      

      const jobFormDiv2 = document.createElement("div");
      jobFormDiv2.style.paddingLeft = "0";
      const jobFormInput1 = document.createElement("input");
      jobFormInput1.setAttribute("id", "job_queued_tagnumber");
      jobFormInput1.setAttribute("name", "job_queued_tagnumber");
      jobFormInput1.setAttribute("type", "hidden");
      jobFormInput1.value = value["tagnumber"];
      const jobFormSelect = document.createElement("select");
      // jobFormInput1.setAttribute("readonly", "true");
      // jobFormInput1.setAttribute("required", "true");
      const jobFormButton = document.createElement("button");
      jobFormButton.setAttribute("id", "job_form_button");
      jobFormButton.setAttribute("type", "submit");
      jobFormButton.classList.add("submit");
      jobFormButton.setAttribute("disabled", "true");
      jobFormButton.innerText = "Loading...";
      if (value["tagnumber"] && value["tagnumber"] > 0) {
        const jobFormOpt1 = document.createElement("option");
        jobFormOpt1.value = "";
        jobFormOpt1.innerText = "--Select Job Below--";
        jobFormOpt1.selected = true;
        jobFormSelect.append(jobFormOpt1);
        Object.entries(availableJobs).forEach(([key1, value1]) => {
          let jobFormOptionN = document.createElement("option");
          jobFormOptionN.value = value1["job"];
          jobFormOptionN.innerText = value1["job_readable"];
          jobFormSelect.append(jobFormOptionN);
        });
      } else {
        jobFormSelect.setAttribute("disabled", "true");
        const jobFormOpt1 = document.createElement("option");
        jobFormOpt1.value = "";
        jobFormOpt1.innerText = "ERR: " + tagnumber + " missing from DB :((("
        jobFormSelect.append(jobFormOpt1);
      }
      

      jobFormDiv1.append(jobFormLabel1);
      jobFormDiv2.append(jobFormInput1, jobFormSelect, jobFormButton);
      jobForm.append(jobFormDiv1, jobFormDiv2);
      jobFormParentDiv.append(jobForm);
      col1.append(jobStatus, jobFormParentDiv);
      parentDiv.append(col1);

      const col2 = document.createElement("div");
      col2.classList.add("flex-container-child");
      col2.style.width = "50%";
      col2.style.height = "100%";
      col2.style.alignSelf = "center";

      Object.entries(liveImage).forEach(([key2, value2]) => {
        const liveImageDiv1 = document.createElement("div");
        const liveImageTimeP1 = document.createElement("p");
        liveImageTimeP1.setAttribute("id", "live_image_timestamp");
        const liveImageTimeText1 = document.createTextNode(value2["time_formatted"]);
        
        const liveImageDiv2 = document.createElement("div");
        const liveImageScreenshot = document.createElement("img");
        liveImageScreenshot.setAttribute("id", "live_image_screenshot");
        liveImageScreenshot.classList.add("live-image");
        liveImageScreenshot.setAttribute("loading", "lazy");

        liveImageTimeP1.append(liveImageTimeText1);
        liveImageDiv1.append(liveImageTimeP1);
        liveImageDiv2.append(liveImageScreenshot);

        col2.append(liveImageDiv1, liveImageDiv2);
        parentDiv.append(col2);
      });

      jobQueueSectionFragment.append(parentDiv);
      jobQueueSectionFragment.replaceChildren(parentDiv);
      if (!document.getElementById("job_queue_div")) {
        oldJobQueueSection.replaceChildren(jobQueueSectionFragment);
      }
    });
  } catch(error) {
    console.error(error);
  }
}

async function updateLiveImage(tagnumber) {
  const liveImage = await fetchData('https://UIT_WAN_IP_ADDRESS:31411/api/remote?type=live_image&tagnumber=' + encodeURIComponent(tagnumber).replace(/'/g, "%27"));
  Object.entries(liveImage).forEach(([key, value]) => {
    const oldScreenshotTime = document.getElementById("live_image_timestamp");
    const oldScreenshotData = document.getElementById("live_image_screenshot");
    
    const newScreenshotTime = value["time_formatted"];
    const newScreenshotData = value["screenshot"];

    oldScreenshotTime.innerText = newScreenshotTime;
    oldScreenshotData.src = "data:image/jpeg;base64," + newScreenshotData;
    oldScreenshotData.setAttribute("onclick", "window.open('/view-images.php?live_image=1&tagnumber=" + encodeURIComponent(tagnumber).replace(/'/g, "%27") + "', '_blank')");
  });
}

async function updateTagnumberData(tagnumber) {
  try {
    const oldGeneralClientInfo = document.getElementById("client_info");

    const generalClientInfoFragment = new DocumentFragment();
    
    const diskInfo = document.createElement("table");
    diskInfo.setAttribute("id", "disk_info");

    

    const cpuRamInfo = document.createElement("table");
    cpuRamInfo.setAttribute("id", "cpu_ram_info");

    const tagnumberData = await fetchData("https://UIT_WAN_IP_ADDRESS:31411/api/remote?type=tagnumber_data&tagnumber=" + encodeURIComponent(tagnumber).replace(/'/g, "%27"));
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

      const locationA1 = document.createElement("a");
      locationA1.style.cursor = "pointer";
      locationA1.style.fontStyle = "italic";
      locationA1.setAttribute("onclick", "newLocationWindow('" + encodeURIComponent(value["location"]).replace(/'/g, "%27") + "', '" + encodeURIComponent(value["tagnumber"]).replace(/'/g, "%27") + "')");
      locationA1.innerText = "(Click to Update Location)";


      const locationP = document.createElement("p");
      locationP.style.marginBottom = "1em";
      locationP.style.marginTop = "1em";
      locationP.append(locationReadable, locationA1);
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
      departmentA1.setAttribute("onclick", "newLocationWindow('" + encodeURIComponent(value["location"]).replace(/'/g, "%27") + "', '" + encodeURIComponent(value["tagnumber"]).replace(/'/g, "%27") + "', '" + encodeURIComponent(value["department"]).replace(/'/g, "%27") + "')");
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
      domainA1.setAttribute("onclick", "newLocationWindow('" + encodeURIComponent(value["location"]).replace(/'/g, "%27") + "', '" + encodeURIComponent(value["tagnumber"]).replace(/'/g, "%27") + "', '" + encodeURIComponent(value["department"]).replace(/'/g, "%27") + "', '" + encodeURIComponent(value["domain_readable"]).replace(/'/g, "%27") + "')");
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
      const macP1 = document.createElement("p");
      const macTD2 = document.createElement("td");
      const macP2 = document.createElement("p");

      const macText1 = document.createTextNode("MAC Address");

      let wifiMacFormatted = undefined;
      if (value["wifi_mac_formatted"] && value["wifi_mac_formatted"].length > 0) {
        wifiMacFormatted = value["wifi_mac_formatted"] + " (Wi-Fi)";
      } else {
        wifiMacFormatted = "";
      }

      let etherAddressFormatted = undefined;
      if (value["etheraddress_formatted"] && value["etheraddress_formatted"].length > 0) {
        if (value["network_speed_formatted"] && value["network_speed_formatted"].length > 0) {
          etherAddressFormatted = value["etheraddress_formatted"] + " (Ethernet) (" + value["network_speed_formatted"] + ")";
        } else {
          etherAddressFormatted = value["etheraddress_formatted"] + " (Ethernet)";
        }
      }


      if (wifiMacFormatted !== undefined && etherAddressFormatted !== undefined) {
        const subMacTable = document.createElement("table");
        subMacTable.style.maxWidth = "fit-content";
        subMacTable.style.marginLeft = "0";
        const subMacTBody = document.createElement("tbody");

        const subMacTr1 = document.createElement("tr");
        subMacTr1.style.borderBottom = "1px solid black";
        const subMacTd1 = document.createElement("td");
        const subMacP1 = document.createElement("p");
        const subMacText1 = document.createTextNode(wifiMacFormatted);

        const subMacTr2 = document.createElement("tr");
        subMacTr2.style.borderBottom = "none";
        const subMacTd2 = document.createElement("td");
        const subMacP2 = document.createElement("p");
        const subMacText2 = document.createTextNode(etherAddressFormatted);

        subMacP1.append(subMacText1);
        subMacTd1.append(subMacP1);
        subMacTr1.append(subMacTd1);
        subMacP2.append(subMacText2);
        subMacTd2.append(subMacP2);
        subMacTr2.append(subMacTd2);
        subMacTBody.append(subMacTr1, subMacTr2);
        subMacTable.append(subMacTBody);
        macTD2.append(subMacTable);
      } else if (wifiMacFormatted !== undefined && etherAddressFormatted === undefined) {
        const macText2 = document.createTextNode(wifiMacFormatted);
        macP2.append(macText2);
        macTD2.append(macP2);
      } else if (wifiMacFormatted === undefined && etherAddressFormatted !== undefined) {
        const macText2 = document.createTextNode(etherAddressFormatted);
        macP2.append(macText2);
        macTD2.append(macP2);
      }

      macP1.append(macText1);
      macTD1.append(macP1);
      macRow.append(macTD1, macTD2);
      

      // System model
      const modelRow = document.createElement("tr");
      const modelTD1 = document.createElement("td");
      const modelP1 = document.createElement("p");
      const modelTD2 = document.createElement("td");
      const modelP2 = document.createElement("p");

      const modelText1 = document.createTextNode("System Model");

      let systemModelFormatted = undefined;
      if (value["system_model_formatted"] && value["system_model_formatted"].length > 0) {
        systemModelFormatted = value["system_model_formatted"];
      } else {
        systemModelFormatted = ""
      }
      const modelText2 = document.createTextNode(systemModelFormatted);

      modelP1.append(modelText1);
      modelTD1.append(modelP1);
      modelP2.append(modelText2);
      modelTD2.append(modelP2);
      modelRow.append(modelTD1, modelTD2)


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


      generalClientBodyTbody.append(locationRow, departmentRow, domainRow, modelRow, osRow, serialRow, macRow, bitlockerRow, biosRow);
      generalClientInfoFragment.replaceChildren(generalClientInfoTable);
      oldGeneralClientInfo.replaceChildren(generalClientInfoFragment);



      // Client health
      const oldClientHealthTable = document.getElementById("client_health");

      const clientHealthFragment = new DocumentFragment();
      const clientHealthTable = document.createElement("table");
      clientHealthTable.setAttribute("id", "client_health");
      clientHealthTable.style.width = '100%';

      // thead
      const clientHealthThead = document.createElement("thead");
      const clientHealthHeadTR1 = document.createElement("tr");
      const clientHealthHeadTH1 = document.createElement("th");
      clientHealthHeadTH1.innerText = "Client Health - " + value["tagnumber"];
      const clientHealthHeadTH2 = document.createElement("th");

      clientHealthHeadTR1.append(clientHealthHeadTH1, clientHealthHeadTH2);
      clientHealthThead.append(clientHealthHeadTR1);

      // tbody
      const clientHealthTbody = document.createElement("tbody");

      // Total jobs
      const totalJobsRow = document.createElement("tr");
      const totalJobsTD1 = document.createElement("td");
      const totalJobsTD2 = document.createElement("td");
      const totalJobsP2 = document.createElement("p");
      
      totalJobsTD1.innerText = "Avg. Erase Time / Avg. Clone Time / Total Jobs";
      
      let allJobs = undefined;
      if (value["all_jobs"] && value["all_jobs"] > 0) {
        allJobs = value["all_jobs"];
      } else {
        allJobs = "Unknown";
      }

      let avgCloneTime = undefined;
      if (value["avg_clone_time"] && value["avg_clone_time"] > 0) {
        avgCloneTime = value["avg_clone_time"];
      } else {
        avgCloneTime = "Unknown";
      }

      let avgEraseTime = undefined;
      if (value["avg_erase_time"] && value["avg_erase_time"] > 0) {
        avgEraseTime = value["avg_erase_time"];
      } else {
        avgEraseTime = "Unknown";
      }

      let totalJobsFormattedString = undefined;
      if (allJobs !== "Unknown" || avgCloneTime !== "Unknown" || avgEraseTime !== "Unknown") {
        totalJobsFormattedString = avgEraseTime + " mins / " + avgCloneTime + " mins / " + allJobs + " jobs";
      } else {
        totalJobsFormattedString = "";
      }
      
      const totalJobsText2 = document.createTextNode(totalJobsFormattedString);
      totalJobsP2.append(totalJobsText2);
      totalJobsTD2.append(totalJobsP2);

      totalJobsRow.append(totalJobsTD1, totalJobsTD2);

      // Battery Health
      const batteryRow = document.createElement("tr");
      const batteryTD1 = document.createElement("td");
      const batteryP1 = document.createElement("p");
      const batteryTD2 = document.createElement("td");
      const batteryP2 = document.createElement("p");

      const batteryText1 = document.createTextNode("Overall Battery Health");

      let batteryText2 = undefined;
      if (value["battery_health"]) {
        batteryText2 = document.createTextNode(value["battery_health"] + "%");
      } else {
        batteryText2 = document.createTextNode("");
      }

      batteryP1.append(batteryText1);
      batteryTD1.append(batteryP1);
      batteryP2.append(batteryText2);
      batteryTD2.append(batteryP2);
      batteryRow.append(batteryTD1, batteryTD2);


      // Disk TBW/TBR
      const diskTBWRow = document.createElement("tr");
      const diskTBWTD1 = document.createElement("td");
      const diskTBWP1 = document.createElement("p");
      const diskTBWTD2 = document.createElement("td");
      const diskTBWP2 = document.createElement("p");

      const diskTBWText1 = document.createTextNode("Disk TBW / TBR");

      let diskWrites = undefined;
      if (value["disk_writes"] && value["disk_writes"] > 0) {
        diskWrites = value["disk_writes"];
      } else {
        diskWrites = "Unknown";
      }

      let diskReads = undefined;
      if (value["disk_reads"] && value["disk_reads"] > 0) {
        diskReads = value["disk_reads"];
      } else {
        diskReads = "Unknown";
      }

      let diskTBWFormattedString = undefined;
      if (diskWrites !== "Unknown" || diskReads !== "Unknown") {
        diskTBWFormattedString = diskWrites + " TBW / " + diskReads + " TBR";
      } else {
        diskTBWFormattedString = ""
      }

      const diskTBWText2 = document.createTextNode(diskTBWFormattedString);
      
      diskTBWP1.append(diskTBWText1);
      diskTBWTD1.append(diskTBWP1);
      diskTBWP2.append(diskTBWText2);
      diskTBWTD2.append(diskTBWP2);
      diskTBWRow.append(diskTBWTD1, diskTBWTD2);


      // Disk power on hours
      const diskHrsRow = document.createElement("tr");
      const diskHrsTD1 = document.createElement("td");
      const diskHrsP1 = document.createElement("p");
      const diskHrsTD2 = document.createElement("td");
      const diskHrsP2 = document.createElement("p");

      const diskHrsText1 = document.createTextNode("Disk Power on Hours");
      
      let diskHrsText2 = undefined;
      if (value["disk_power_on_hours"] && value["disk_power_on_hours"] > 0) {
        diskHrsText2 = document.createTextNode(value["disk_power_on_hours"] + " hrs");
      } else {
        diskHrsText2 = document.createTextNode("");
      }

      diskHrsP1.append(diskHrsText1);
      diskHrsTD1.append(diskHrsP1);
      diskHrsP2.append(diskHrsText2);
      diskHrsTD2.append(diskHrsP2);
      diskHrsRow.append(diskHrsTD1, diskHrsTD2);


      // Disk power cycles
      const diskCyclesRow = document.createElement("tr");
      const diskCyclesTD1 = document.createElement("td");
      const diskCyclesP1 = document.createElement("p");
      const diskCyclesTD2 = document.createElement("td");
      const diskCyclesP2 = document.createElement("p");

      const diskCyclesText1 = document.createTextNode("Disk Power Cycles");

      let diskCyclesFormatted = undefined;
      if (value["disk_power_cycles"] && value["disk_power_cycles"] > 0) {
        diskCyclesFormatted = value["disk_power_cycles"] + " cycles";
      } else {
        diskCyclesFormatted = "";
      }
      const diskCyclesText2 = document.createTextNode(diskCyclesFormatted);

      diskCyclesP1.append(diskCyclesText1);
      diskCyclesTD1.append(diskCyclesP1);
      diskCyclesP2.append(diskCyclesText2);
      diskCyclesTD2.append(diskCyclesP2);
      diskCyclesRow.append(diskCyclesTD1, diskCyclesTD2);


      // Disk errors
      const diskErrorsRow = document.createElement("tr");
      const diskErrorsTD1 = document.createElement("td");
      const diskErrorsP1 = document.createElement("p");
      const diskErrorsTD2 = document.createElement("td");
      const diskErrorsP2 = document.createElement("p");

      const diskErrorsText1 = document.createTextNode("Disk Errors");

      let diskErrorsFormatted = undefined;
      if (value["disk_errors"] || value["disk_errors"] == 0) {
        diskErrorsFormatted = value["disk_errors"] + " errors";
      } else {
        diskErrorsFormatted = "";
      }
      const diskErrorsText2 = document.createTextNode(diskErrorsFormatted);

      diskErrorsP1.append(diskErrorsText1);
      diskErrorsTD1.append(diskErrorsP1);
      diskErrorsP2.append(diskErrorsText2);
      diskErrorsTD2.append(diskErrorsP2);
      diskErrorsRow.append(diskErrorsTD1, diskErrorsTD2);


      // Disk health
      const diskHealthRow = document.createElement("tr");
      const diskHealthTD1 = document.createElement("td");
      const diskHealthP1 = document.createElement("p");
      const diskHealthTD2 = document.createElement("td");
      const diskHealthP2 = document.createElement("p");

      const diskHealthText1 = document.createTextNode("Overall Disk Health");

      let diskHealthFormatted = undefined;
      if (value["disk_health"] || value["disk_health"] == 0) {
        diskHealthFormatted = value["disk_health"] + "%";
      } else {
        diskHealthFormatted = "";
      }

      const diskHealthText2 = document.createTextNode(diskHealthFormatted);

      diskHealthP1.append(diskHealthText1);
      diskHealthTD1.append(diskHealthP1);
      diskHealthP2.append(diskHealthText2);
      diskHealthTD2.append(diskHealthP2);
      diskHealthRow.append(diskHealthTD1, diskHealthTD2);


      clientHealthTbody.append(totalJobsRow, batteryRow, diskTBWRow, diskHrsRow, diskCyclesRow, diskErrorsRow, diskHealthRow);
      clientHealthTable.append(clientHealthThead, clientHealthTbody);
      clientHealthFragment.replaceChildren(clientHealthTable);
      oldClientHealthTable.replaceChildren(clientHealthFragment);




      // Hardware Data
      const oldHardwareTable = document.getElementById("hardware_data");
      const hardwareFragment = new DocumentFragment();
      const hardwareTable = document.createElement("table");
      const hardwareTableHead = document.createElement("thead");
      const hardwareTableBody = document.createElement("tbody");

      const hardwareTableHeadTr = document.createElement("tr");
      const hardwareTableHeadTh1 = document.createElement("th");
      const hardwareTableHeadTh2 = document.createElement("th");
      
      hardwareTableHeadTh1.innerText = "Hardware Data - " + value["tagnumber"];

      hardwareTableHeadTr.append(hardwareTableHeadTh1, hardwareTableHeadTh2);
      hardwareTableHead.append(hardwareTableHeadTr);

      // CPU model
      const cpuModelRow = document.createElement("tr");
      const cpuModelTd1 = document.createElement("td");
      const cpuModelP1 = document.createElement("p");
      const cpuModelTd2 = document.createElement("td");
      const cpuModelP2 = document.createElement("p");

      const cpuModelText1 = document.createTextNode("CPU Model");
      
      let cpuModelFormatted = undefined;
      if (value["cpu_model"]) {
        cpuModelFormatted = value["cpu_model"];
      } else {
        cpuModelFormatted = "";
      }
      const cpuModelText2 = document.createTextNode(cpuModelFormatted);

      cpuModelP1.append(cpuModelText1);
      cpuModelTd1.append(cpuModelP1);
      cpuModelP2.append(cpuModelText2);
      cpuModelTd2.append(cpuModelP2);
      cpuModelRow.append(cpuModelTd1, cpuModelTd2);


      // Cpu Cores
      const cpuCoresRow = document.createElement("tr");
      const cpuCoresTd1 = document.createElement("td");
      const cpuCoresP1 = document.createElement("p");
      const cpuCoresTd2 = document.createElement("td");
      const cpuCoresP2 = document.createElement("p");

      const cpuCoresText1 = document.createTextNode("CPU Cores");

      let cpuMultithreadedFormatted = undefined;
      if (value["multithreaded_formatted"]) {
        cpuMultithreadedFormatted = value["multithreaded_formatted"];
      }

      let cpuMaxSpeedFormatted = undefined;
      if (value["cpu_maxspeed_formatted"]) {
        cpuMaxSpeedFormatted = value["cpu_maxspeed_formatted"];
      }

      let cpuCoresFormatted = undefined;
      if (cpuMultithreadedFormatted && cpuMaxSpeedFormatted) {
        cpuCoresFormatted = cpuMultithreadedFormatted + " " + cpuMaxSpeedFormatted;
      } else {
        cpuCoresFormatted = "";
      }
      const cpuCoresText2 = document.createTextNode(cpuCoresFormatted);

      cpuCoresP1.append(cpuCoresText1);
      cpuCoresTd1.append(cpuCoresP1);
      cpuCoresP2.append(cpuCoresText2);
      cpuCoresTd2.append(cpuCoresP2);
      cpuCoresRow.append(cpuCoresTd1, cpuCoresTd2);


      // RAM capacity
      const ramRow = document.createElement("tr");
      const ramTd1 = document.createElement("td");
      const ramP1 = document.createElement("p");
      const ramTd2 = document.createElement("td");
      const ramP2 = document.createElement("p");

      const ramText1 = document.createTextNode("RAM Capacity");

      let ramFormatted = undefined;
      if (value["ram_capacity_formatted"] && value["ram_capacity_formatted"].length > 0) {
        ramFormatted = value["ram_capacity_formatted"];
      } else {
        ramFormatted = "";
      }
      const ramText2 = document.createTextNode(ramFormatted);

      ramP1.append(ramText1);
      ramTd1.append(ramP1);
      ramP2.append(ramText2);
      ramTd2.append(ramP2);
      ramRow.append(ramTd1, ramTd2);


      // Disk Model
      const diskModelRow = document.createElement("tr");
      const diskModelTd1 = document.createElement("td");
      const diskModelP1 = document.createElement("p");
      const diskModelTd2 = document.createElement("td");
      const diskModelP2 = document.createElement("p");

      const diskModelText1 = document.createTextNode("Disk Model");

      let diskModelFormatted = undefined;
      if (value["disk_model"] && value["disk_model"].length > 0) {
        diskModelFormatted = value["disk_model"];
      } else {
        diskModelFormatted = "";
      }
      const diskModelText2 = document.createTextNode(diskModelFormatted);

      diskModelP1.append(diskModelText1);
      diskModelTd1.append(diskModelP1);
      diskModelP2.append(diskModelText2);
      diskModelTd2.append(diskModelP2);
      diskModelRow.append(diskModelTd1, diskModelTd2);


      // Disk Serial
      const diskSerialRow = document.createElement("tr");
      const diskSerialTd1 = document.createElement("td");
      const diskSerialP1 = document.createElement("p");
      const diskSerialTd2 = document.createElement("td");
      const diskSerialP2 = document.createElement("p");

      const diskSerialText1 = document.createTextNode("Disk Serial");

      let diskSerialFormatted = undefined;
      if (value["disk_serial"] && value["disk_serial"].length > 0) {
        diskSerialFormatted = value["disk_serial"];
      } else {
        diskSerialFormatted = "";
      }
      const diskSerialText2 = document.createTextNode(diskSerialFormatted);

      diskSerialP1.append(diskSerialText1);
      diskSerialTd1.append(diskSerialP1);
      diskSerialP2.append(diskSerialText2);
      diskSerialTd2.append(diskSerialP2);
      diskSerialRow.append(diskSerialTd1, diskSerialTd2);


      // Disk type
      const diskTypeRow = document.createElement("tr");
      const diskTypeTd1 = document.createElement("td");
      const diskTypeP1 = document.createElement("p");
      const diskTypeTd2 = document.createElement("td");
      const diskTypeP2 = document.createElement("p");

      const diskTypeText1 = document.createTextNode("Disk Type");

      let diskTypeFormatted = undefined;
      if (value["disk_type"] && value["disk_type"].length > 0) {
        diskTypeFormatted = value["disk_type"];
      } else {
        diskTypeFormatted = "";
      }
      const diskTypeText2 = document.createTextNode(diskTypeFormatted);

      diskTypeP1.append(diskTypeText1);
      diskTypeTd1.append(diskTypeP1);
      diskTypeP2.append(diskTypeText2);
      diskTypeTd2.append(diskTypeP2);
      diskTypeRow.append(diskTypeTd1, diskTypeTd2);


      // Disk size
      const diskSizeRow = document.createElement("tr");
      const diskSizeTd1 = document.createElement("td");
      const diskSizeP1 = document.createElement("p");
      const diskSizeTd2 = document.createElement("td");
      const diskSizeP2 = document.createElement("p");

      const diskSizeText1 = document.createTextNode("Disk Size");

      let diskSizeFormatted = undefined;
      if (value["disk_size"] && value["disk_size"].length > 0) {
        diskSizeFormatted = value["disk_size"] + " GB";
      } else {
        diskSizeFormatted = "";
      }
      const diskSizeText2 = document.createTextNode(diskSizeFormatted);

      diskSizeP1.append(diskSizeText1);
      diskSizeTd1.append(diskSizeP1);
      diskSizeP2.append(diskSizeText2);
      diskSizeTd2.append(diskSizeP2);
      diskSizeRow.append(diskSizeTd1, diskSizeTd2);

      



      hardwareTableBody.append(cpuModelRow, cpuCoresRow, ramRow, diskSizeRow, diskModelRow, diskSerialRow, diskTypeRow);
      hardwareTable.append(hardwareTableHead, hardwareTableBody);
      hardwareFragment.replaceChildren(hardwareTable);
      oldHardwareTable.replaceChildren(hardwareFragment);

    });

  } catch(error) {
    console.error(error);
  }

}

async function autoFillTags() {
  try {
    tagJson = await fetchData('https://UIT_WAN_IP_ADDRESS:31411/api/locations?type=all_tags');

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
