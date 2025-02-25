# Setup script for CarMarket project
Write-Host "Setting up CarMarket project..." -ForegroundColor Green

# Create directories if they don't exist
$directories = @(
    "data",
    "logs"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        Write-Host "Creating directory: $dir" -ForegroundColor Yellow
        New-Item -ItemType Directory -Force -Path $dir
        
        # Set permissions
        $acl = Get-Acl $dir
        $permission = "Everyone","FullControl","Allow"
        $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule $permission
        $acl.SetAccessRule($accessRule)
        Set-Acl $dir $acl
        Write-Host "Permissions set for: $dir" -ForegroundColor Green
    }
}

# Initialize JSON files
$jsonFiles = @{
    "data/users.json" = @{
        users = @()
        login_attempts = @()
        reset_tokens = @()
        remember_tokens = @()
        verification_tokens = @()
        activity_logs = @()
        security_logs = @()
    }
    "data/user_sessions.json" = @{
        items = @()
    }
    "data/user_activities.json" = @{
        items = @()
    }
}

foreach ($file in $jsonFiles.Keys) {
    if (-not (Test-Path $file)) {
        Write-Host "Creating file: $file" -ForegroundColor Yellow
        $jsonFiles[$file] | ConvertTo-Json -Depth 10 | Set-Content $file -Encoding UTF8
        
        # Set permissions
        $acl = Get-Acl $file
        $permission = "Everyone","FullControl","Allow"
        $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule $permission
        $acl.SetAccessRule($accessRule)
        Set-Acl $file $acl
        Write-Host "Permissions set for: $file" -ForegroundColor Green
    }
}

# Create empty log file if it doesn't exist
if (-not (Test-Path "logs/error.log")) {
    Write-Host "Creating error log file" -ForegroundColor Yellow
    New-Item -ItemType File -Force -Path "logs/error.log"
    
    # Set permissions
    $acl = Get-Acl "logs/error.log"
    $permission = "Everyone","FullControl","Allow"
    $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule $permission
    $acl.SetAccessRule($accessRule)
    Set-Acl "logs/error.log" $acl
    Write-Host "Permissions set for: logs/error.log" -ForegroundColor Green
}

Write-Host "`nSetup completed successfully!" -ForegroundColor Green
Write-Host "You can now run the tests using: .\tests\Integration\run_tests.bat" -ForegroundColor Cyan 