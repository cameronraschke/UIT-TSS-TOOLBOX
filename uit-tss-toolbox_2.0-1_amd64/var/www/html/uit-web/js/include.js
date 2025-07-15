let openedWindow;

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

}

function closeLocationWindow() {
  openedWindow.close();
}

openedWindow.addEventListener('DOMContentLoaded', () => {
    const closeButton = openedWindow.document.getElementById('closeButton');
    closeButton.addEventListener('click', () => {
        window.close();
    });
});

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
function autoFillTags(input) {
  
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
      dropdownHTML = "<ul>";
      allMatches.slice(0,6).forEach(element => {
        if (allMatches[0] == element) {
          dropdownHTML += "<li class='dropdown-search' style='float: none;'><a class='dropdown-search' style='float: none; color: black; background-color:rgb(170, 170, 170);' href='/tagnumber.php?tagnumber=" + element + "'>" + element + "</a>" + "</li>";
        } else {
          dropdownHTML += "<li class='dropdown-search' style='float: none;'><a class='dropdown-search' style='float: none; color: black;' href='/tagnumber.php?tagnumber=" + element + "'>" + element + "</a>" + "</li>";
        }
      });
      dropdownHTML += "</ul>";
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


async function fetchData(url) {
  const url = url;
  try {
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }

    const json = await response.json();
    console.log(json);
  } catch (error) {
    console.error(error.message);
  }
}