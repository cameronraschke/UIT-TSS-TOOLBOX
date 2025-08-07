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


async function remotePresentTable() {
  const remoteTable = document.getElementById('remoteTable');
  tableData = await fetchData('https://WAN_IP_ADDRESS/api/remote?type=remote_present');
  tableData.forEach (row => {
    let row = document.createElement("tr");
    Object.values(row).forEach((value) => {
      let cell = document.createElement("td");
      cell.innerText = value;
      row.appendChild(cell)
    })
    remoteTable.appendChild(row);
  });
}