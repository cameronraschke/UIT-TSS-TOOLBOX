let openedWindow;

function newLocationWindow(location, tagnumber) {
  //openedWindow = window.open("/locations.php?location=" + location + "&tagnumber=" + tagnumber);
  openedWindow = window.location.assign("/locations.php?ref=1&location=" + location + "&tagnumber=" + tagnumber);
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