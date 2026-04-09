# Intelligent Sync Script
# Server Info
$IP = "159.65.144.8"
$PORT = "8609"
$USER = "suadmin"
$REMOTE_PATH = "/var/www/dppic.com/gallery/home/"

# Folders to sync
$SyncPaths = @(
	"public", 
    "admin", 
    "core", 
    "public", 
	"test", 
	"standalone-generator",
    "templates"
)

# Smart Sync Logic (Force full sync by deleting .sync_marker or setting $ForceFullSync = $true)
$MarkerFile = ".sync_marker"
$ForceFullSync = $false 

$PlainPassword = "Chennai@1234"

# Determine Sync Threshold
if (-not (Test-Path $MarkerFile) -or $ForceFullSync) {
    $SyncTime = [DateTime]::MinValue
    Write-Host "Starting FULL Sync..." -ForegroundColor Magenta
}
else {
    $SyncTime = (Get-Item $MarkerFile).LastWriteTime
    Write-Host "Starting SMART Sync (Files changed since $($SyncTime.ToString('HH:mm:ss')))..." -ForegroundColor Magenta
}

$CurrentRunTime = Get-Date

# Ensure remote directories exist for sync paths
if ($SyncTime -eq [DateTime]::MinValue) {
    Write-Host "Creating remote directory structure..." -ForegroundColor Cyan
    foreach ($path in $SyncPaths) {
        $RemoteTarget = "$REMOTE_PATH/$($path -replace '\\', '/')"
        # Using ssh to create directory
        plink -batch -P $PORT -pw "$PlainPassword" "$($USER)@$IP" "mkdir -p $RemoteTarget"
    }
}

$TransferredCount = 0

foreach ($path in $SyncPaths) {
    if (Test-Path $path) {
        # Find files modified since last sync
        $Files = Get-ChildItem -Path $path -Recurse -File | Where-Object { $_.LastWriteTime -gt $SyncTime }
        
        foreach ($file in $Files) {
            $RelativePath = Resolve-Path $file.FullName -Relative
            $RelativePath = $RelativePath -replace "^\.\\", ""

            # ===== EXCLUSION RULES =====
            # Skip image files (they are uploaded via admin panel, not synced via FTP)
            if ($file.Extension -match "\.(jpg|jpeg|png|gif|webp|bmp|svg|ico)$") {
                continue
            }
            # Skip generated static HTML files in the public folder (they are built server-side)
            if ($RelativePath -match "^public\\" -and $file.Extension -eq ".html") {
                continue
            }
            # Skip uploads directory entirely
            if ($RelativePath -match "^public\\assets\\images\\uploads\\") {
                continue
            }
            # Skip standalone-generator local output directory
            if ($RelativePath -match "^standalone-generator[\\/]output[\\/]") {
                continue
            }
            # ===========================
            
            # Identify target sub-directory
            $SubDir = Split-Path $RelativePath -Parent
            $RemoteTarget = "$REMOTE_PATH/$($SubDir -replace '\\', '/')"
            
            # Ensure sub-directory exists if recursively deep
            if ($SubDir -and $SyncTime -ne [DateTime]::MinValue) {
                plink -batch -P $PORT -pw "$PlainPassword" "$($USER)@$IP" "mkdir -p $RemoteTarget"
            }

            Write-Host "Syncing: $RelativePath" -ForegroundColor Yellow
            pscp -q -P $PORT -pw "$PlainPassword" "$RelativePath" "$($USER)@$($IP):$RemoteTarget"
            $TransferredCount++
        }
    }
}

# Sync all PHP/SQL files in the root directory
$RootPhpFiles = Get-ChildItem -Path "." -Filter "*.*" -File | Where-Object {
    $_.Name -match "\.(php|sql|md)$" -and $_.LastWriteTime -gt $SyncTime
}

foreach ($file in $RootPhpFiles) {
    Write-Host "Syncing root file: $($file.Name)" -ForegroundColor Yellow
    pscp -q -P $PORT -pw "$PlainPassword" "$($file.Name)" "$($USER)@$($IP):$REMOTE_PATH/"
    $TransferredCount++
}

# Update marker and finish
if (-not (Test-Path $MarkerFile)) {
    New-Item -Path $MarkerFile -ItemType File -Force | Out-Null
}
(Get-Item $MarkerFile).LastWriteTime = $CurrentRunTime

if ($TransferredCount -eq 0) {
    Write-Host "Nothing to sync. Everything is up to date!" -ForegroundColor Cyan
}
else {
    Write-Host "Global Sync Complete! ($TransferredCount files transferred)" -ForegroundColor Green
    Write-Host "All logic, styles, and layouts are now live."
}

# Finally, execute an automatic server-side permission fix to guarantee Webserver write ability
Write-Host "Re-applying Webserver 777 Permissions on remote directories..." -ForegroundColor Yellow
plink -batch -P $PORT -pw "$PlainPassword" "$($USER)@$IP" "echo 'Chennai@1234' | sudo -S chmod -R 777 /var/www/dppic.com/gallery/home/admin/standalone-generator"
Write-Host "Permission logic complete!" -ForegroundColor Green
