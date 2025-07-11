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
tagStr = input.toString();
let availableTagnumbers = tagStr.split('|');

  var tagnumberField = document.getElementById('tagnumber-search');

  tagnumberField.addEventListener('keyup', (event) => {
    const inputText = tagnumberField.value;

    if (event.key === 'Backspace' || event.key === 'Delete') {
      tagnumberField.value = tagnumberField.value.substr(0, getCursorPos(tagnumberField));
      //console.log("Backspace Value: " + tagnumberField.value);
      //console.log("Backspace Position: " + getCursorPos(tagnumberField) + ", " + getCursorPos(tagnumberField));
      tagnumberField.setSelectionRange(getCursorPos(tagnumberField), getCursorPos(tagnumberField));
    }
  });

  tagnumberField.addEventListener('input', function() {
    const inputText = tagnumberField.value;
    var re = new RegExp('^' + inputText, 'gi');
    var re1 = new RegExp('^' + inputText + '$', 'gi');
    const matchingExact = availableTagnumbers.find(suggestion => suggestion.match(re1));
    const matchingSuggestion = availableTagnumbers.find(suggestion => suggestion.match(re));

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
