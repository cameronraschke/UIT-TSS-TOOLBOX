$aacsp = Get-CimInstance -Namespace "root\cimv2\mdm\dmmap" -ClassName "MDM_AssignedAccess"
$aacsp.Configuration = $NULL
Set-CimInstance -CimInstance $aacsp

Remove-LocalUser -Name "kioskUser0"

$assignedAccessConfiguration = @"
<?xml version="1.0" encoding="utf-8"?>
<AssignedAccessConfiguration xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns="http://schemas.microsoft.com/AssignedAccess/2017/config" xmlns:default="http://schemas.microsoft.com/AssignedAccess/2017/config" xmlns:rs5="http://schemas.microsoft.com/AssignedAccess/201810/config" xmlns:v3="http://schemas.microsoft.com/AssignedAccess/2020/config" xmlns:v5="http://schemas.microsoft.com/AssignedAccess/2022/config">
  <Profiles>
    <Profile Id="{bd2d365d-da9b-4577-bffd-22d5fcd98477}">
      <AllAppsList>
        <AllowedApps>
          <App AppUserModelId="Microsoft.Paint_8wekyb3d8bbwe!App" />
          <App AppUserModelId="Microsoft.MicrosoftOfficeHub_8wekyb3d8bbwe!Microsoft.MicrosoftOfficeHub" />
	        <App AppUserModelId="{6D809377-6AF0-444B-8957-A3773F02200E}\Windows NT\Accessories\wordpad.exe" />
          <App AppUserModelId="Microsoft.WindowsNotepad_8wekyb3d8bbwe!App" />
          <App AppUserModelId="Microsoft.Windows.Photos_8wekyb3d8bbwe!App" />
          <App AppUserModelId="Microsoft.ZuneMusic_8wekyb3d8bbwe!Microsoft.ZuneMusic" />
          <App AppUserModelId="Microsoft.WindowsCalculator_8wekyb3d8bbwe!App" />
          <App AppUserModelId="Microsoft.Windows.Explorer" />
          <App AppUserModelId="Microsoft.Office.EXCEL.EXE.15" />
          <App AppUserModelId="Microsoft.Office.ONENOTE.EXE.15" />
          <App AppUserModelId="Microsoft.OutlookForWindows_8wekyb3d8bbwe!Microsoft.OutlookforWindows" />
          <App AppUserModelId="Microsoft.Office.OUTLOOK.EXE.15" />
          <App AppUserModelId="Microsoft.Office.POWERPNT.EXE.15" />
          <App AppUserModelId="Microsoft.Office.WINWORD.EXE.15" />
          <App AppUserModelId="MSEdge" />
          <App AppUserModelId="MSTeams_8wekyb3d8bbwe!MSTeams" />
          <App DesktopAppPath="C:\Program Files\Google\Chrome\Application\chrome.exe" />
          <App DesktopAppPath="C:\Program Files\Mozilla Firefox\firefox.exe" />
          <App DesktopAppPath="C:\Program Files\Mozilla Firefox\private_browsing.exe" />
          <App AppUserModelId="{1AC14E77-02E7-4E5D-B744-2EB1AE5198B7}\printmanagement.msc" />
          <App DesktopAppPath="C:\Program Files (x86)\Pharos\bin\popupcli.exe" />
          <App DesktopAppPath="C:\Program Files (x86)\Pharos\bin\Console-Notifier.exe" />
          <App DesktopAppPath="C:\Program Files (x86)\Pharos\bin\PopupClientConfiguration.exe" />
          <App DesktopAppPath="C:\Program Files (x86)\Pharos\bin\popnet.exe" />
          <App DesktopAppPath="C:\Program Files (x86)\Pharos\bin\Local.EXE" />
          <App DesktopAppPath="C:\Program Files (x86)\PharosSystems\Core\CTskMstr.exe" />
          <App DesktopAppPath="C:\Program Files (x86)\PharosSystems\Core\UserServer.exe" />
        </AllowedApps>
      </AllAppsList>
      <v5:StartPins><![CDATA[{
        "pinnedList":
        [
          {"desktopAppLink":"%APPDATA%\\Microsoft\\Windows\\Start Menu\\Programs\\File Explorer.lnk"},
          {"desktopAppLink":"%ALLUSERSPROFILE%\\Microsoft\\Windows\\Start Menu\\Programs\\Google Chrome.lnk"},
          {"desktopAppLink":"%ALLUSERSPROFILE%\\Microsoft\\Windows\\Start Menu\\Programs\\Firefox.lnk"},
          {"desktopAppLink":"%ALLUSERSPROFILE%\\Microsoft\\Windows\\Start Menu\\Programs\\Firefox Private Browsing.lnk"},
          {"desktopAppLink":"%ALLUSERSPROFILE%\\Microsoft\\Windows\\Start Menu\\Programs\\Word.lnk"},
          {"desktopAppLink":"%ALLUSERSPROFILE%\\Microsoft\\Windows\\Start Menu\\Programs\\OneNote.lnk"},
          {"packagedAppId":"MSTeams_8wekyb3d8bbwe!MSTeams"},
          {"packagedAppId":"Microsoft.OutlookForWindows_8wekyb3d8bbwe!Microsoft.OutlookforWindows"},
          {"desktopAppLink":"%ALLUSERSPROFILE%\\Microsoft\\Windows\\Start Menu\\Programs\\PowerPoint.lnk"},
          {"desktopAppLink":"%ALLUSERSPROFILE%\\Microsoft\\Windows\\Start Menu\\Programs\\Excel.lnk"}
        ]
        }]]></v5:StartPins>
	    <Taskbar ShowTaskbar="true" />
	    <v5:TaskbarLayout><![CDATA[
        <?xml version="1.0" encoding="utf-8"?>
        <LayoutModificationTemplate
            xmlns="http://schemas.microsoft.com/Start/2014/LayoutModification"
            xmlns:defaultlayout="http://schemas.microsoft.com/Start/2014/FullDefaultLayout"
            xmlns:start="http://schemas.microsoft.com/Start/2014/StartLayout"
            xmlns:taskbar="http://schemas.microsoft.com/Start/2014/TaskbarLayout"
            Version="1">
          <CustomTaskbarLayoutCollection PinListPlacement="Replace">
            <defaultlayout:TaskbarLayout>
              <taskbar:TaskbarPinList>
                <taskbar:DesktopApp DesktopApplicationID="Microsoft.Windows.Explorer" />
                <taskbar:DesktopApp DesktopApplicationLinkPath="C:\ProgramData\Microsoft\Windows\Start Menu\Programs\Google Chrome.lnk" />
                <taskbar:DesktopApp DesktopApplicationID="MSTeams_8wekyb3d8bbwe!MSTeams" />
                <taskbar:DesktopApp DesktopApplicationID="Microsoft.OutlookForWindows_8wekyb3d8bbwe!Microsoft.OutlookforWindows" />
                <taskbar:DesktopApp DesktopApplicationID="Microsoft.Office.WINWORD.EXE.15" />
                <taskbar:DesktopApp DesktopApplicationID="Microsoft.Office.ONENOTE.EXE.15" />
                <taskbar:DesktopApp DesktopApplicationID="Microsoft.Office.POWERPNT.EXE.15" />
                <taskbar:DesktopApp DesktopApplicationID="Microsoft.Office.EXCEL.EXE.15" />
              </taskbar:TaskbarPinList>
            </defaultlayout:TaskbarLayout>
          </CustomTaskbarLayoutCollection>
        </LayoutModificationTemplate>
        ]]></v5:TaskbarLayout>
    </Profile>
  </Profiles>
  <Configs>
    <Config>
      <AutoLogonAccount rs5:DisplayName="Tech Commons Kiosk" />
      <DefaultProfile Id="{bd2d365d-da9b-4577-bffd-22d5fcd98477}" />
    </Config>
  </Configs>
</AssignedAccessConfiguration>
"@

$namespaceName="root\cimv2\mdm\dmmap"
$className="MDM_AssignedAccess"
$obj = Get-CimInstance -Namespace $namespaceName -ClassName $className
$obj.Configuration = [System.Net.WebUtility]::HtmlEncode($assignedAccessConfiguration)
Set-CimInstance -CimInstance $obj

Remove-Item -Path C:\Users\kioskUser* -Recurse -Force