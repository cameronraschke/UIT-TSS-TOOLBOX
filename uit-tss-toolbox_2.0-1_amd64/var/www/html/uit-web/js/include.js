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
  const blob = new Blob([byteArray], { type: "image/png" });
  const blobUrl = URL.createObjectURL(blob);
  window.open(blobUrl);
  //const newTab = window.open("data:image/jpeg;base64," + imageData);
}