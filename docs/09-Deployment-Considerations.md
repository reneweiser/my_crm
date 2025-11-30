# Deployment Considerations

## Infrastructure Requirements

**Production Server Specifications**:
- **OS**: Ubuntu 22.04 LTS or similar
- **PHP**: 8.4+
- **Web Server**: Nginx (recommended) or Apache
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Memory**: Minimum 2GB RAM (4GB recommended)
- **Storage**: 50GB+ (grows with PDF storage)
- **SSL**: Required for HTTPS and secure email sending

### Hosting Options

**Shared Hosting** (Small scale):
- Traditional providers: Strato, 1&1, Hetzner
- Managed Laravel hosting: Laravel Forge, Ploi, ServerPilot
- **Pros**: Simple setup, cost-effective
- **Cons**: Limited control, potential performance issues

**VPS/Cloud** (Recommended):
- Hetzner Cloud (German data centers - ideal for GDPR)
- DigitalOcean, Linode, Vultr
- AWS EC2, Google Cloud Compute
- **Pros**: Full control, scalable, better performance
- **Cons**: Requires server management knowledge

**Managed Laravel Hosting**:
- Laravel Forge + Hetzner/DigitalOcean
- Cloudways
- **Pros**: Automated deployment, monitoring, backups
- **Cons**: Higher cost

### Deployment Checklist

**Pre-Deployment**:
- [ ] Environment configuration (.env) prepared
- [ ] Database created and credentials configured
- [ ] Company information configured in CRM config
- [ ] Tax IDs and bank details entered
- [ ] Mail server configured and tested
- [ ] SSL certificate installed
- [ ] File permissions set correctly (storage/, bootstrap/cache/)
- [ ] Composer dependencies installed (production mode)
- [ ] Node dependencies installed and assets built
- [ ] Application key generated
- [ ] Database migrations run
- [ ] Initial admin user created

**Deployment Commands**:
```bash
# On production server

# 1. Clone repository
git clone <repository-url> /var/www/my_crm
cd /var/www/my_crm

# 2. Install dependencies (production)
composer install --optimize-autoloader --no-dev
npm install --production
npm run build

# 3. Configure environment
cp .env.example .env
nano .env  # Edit with production values

# 4. Generate application key
php artisan key:generate

# 5. Run migrations
php artisan migrate --force

# 6. Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize

# 7. Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 8. Create admin user
php artisan make:filament-user
```

### Web Server Configuration

**Nginx Configuration** (`/etc/nginx/sites-available/my_crm`):
```nginx
server {
    listen 80;
    server_name crm.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name crm.yourdomain.com;
    root /var/www/my_crm/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/crm.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/crm.yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # File upload size (for PDFs)
    client_max_body_size 20M;

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### Queue Worker Setup

**Supervisor Configuration** (`/etc/supervisor/conf.d/my_crm-worker.conf`):
```ini
[program:my_crm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/my_crm/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/my_crm/storage/logs/worker.log
stopwaitsecs=3600
```

**Start Supervisor**:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start my_crm-worker:*
```

### Scheduled Tasks (Cron)

**Add to Crontab** (`crontab -e` as www-data user):
```cron
* * * * * cd /var/www/my_crm && php artisan schedule:run >> /dev/null 2>&1
```

**Scheduled Commands** (`app/Console/Kernel.php`):
```php
protected function schedule(Schedule $schedule)
{
    // Check for overdue invoices daily
    $schedule->command('invoices:check-overdue')->daily();
    
    // Send payment reminders weekly
    $schedule->command('invoices:send-reminders')->weekly();
    
    // Backup database daily
    $schedule->command('backup:run')->daily()->at('02:00');
    
    // Clean old backups monthly
    $schedule->command('backup:clean')->monthly();
    
    // Update quote statuses (mark expired)
    $schedule->command('quotes:update-status')->daily();
}
```

### Backup Strategy

**Automated Database Backups**:
```bash
# Install spatie/laravel-backup
composer require spatie/laravel-backup

# Configure in config/backup.php
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

**Backup Configuration** (`config/backup.php`):
```php
'backup' => [
    'name' => env('APP_NAME', 'my_crm'),
    
    'source' => [
        'files' => [
            'include' => [
                storage_path('app/invoices'),
                storage_path('app/quotes'),
            ],
            'exclude' => [
                storage_path('app/public'),
            ],
        ],
        'databases' => ['mysql'],
    ],
    
    'destination' => [
        'disks' => ['backup', 's3'],
    ],
    
    'backup_frequency' => 'daily',
],

'cleanup' => [
    'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,
    'defaultStrategy' => [
        'keepAllBackupsForDays' => 7,
        'keepDailyBackupsForDays' => 16,
        'keepWeeklyBackupsForWeeks' => 8,
        'keepMonthlyBackupsForMonths' => 4,
        'keepYearlyBackupsForYears' => 2,
        'deleteOldestBackupsWhenUsingMoreMegabytesThan' => 5000,
    ],
],
```

**Off-Site Backup Storage** (S3-compatible):
```env
# .env
BACKUP_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=my-crm-backups
```

### Monitoring & Logging

**Application Monitoring**:
- Laravel Telescope (development/staging only)
- Laravel Horizon (queue monitoring)
- Sentry or Bugsnag (error tracking)
- New Relic or Datadog (APM)

**Server Monitoring**:
- Disk space (PDF storage grows over time)
- Database size
- Queue length and worker status
- Failed job count
- Email delivery rate

**Log Management**:
```php
// config/logging.php - Production logging
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
    ],
    
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'error', // Only errors in production
        'days' => 30,
    ],
    
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'level' => 'critical', // Critical errors to Slack
    ],
],
```

### Performance Optimization

**Redis Cache**:
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

**Database Optimization**:
```bash
# Regular database optimization
php artisan db:optimize

# Index optimization (analyze query performance)
# Add indexes to frequently queried columns
```

**Asset Optimization**:
```bash
# Production build
npm run build

# Optimize images
# Use WebP format for better compression
```

**OPcache Configuration** (`/etc/php/8.4/fpm/conf.d/99-opcache.ini`):
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
```

### Zero-Downtime Deployment

**Using Laravel Forge or Manual Envoyer**:
```bash
# Deployment script
git pull origin main
composer install --optimize-autoloader --no-dev
npm install --production
npm run build

# Migrate database
php artisan migrate --force

# Clear and recache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Reload PHP-FPM
sudo service php8.4-fpm reload

# Restart queue workers
sudo supervisorctl restart my_crm-worker:*
```

### Security Hardening

**File Permissions**:
```bash
# Laravel recommended permissions
find /var/www/my_crm -type f -exec chmod 644 {} \;
find /var/www/my_crm -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data /var/www/my_crm
```

**Environment Security**:
```bash
# Protect .env file
chmod 600 .env
chown www-data:www-data .env

# Disable directory listing
# Already handled in Nginx config above
```

**Regular Updates**:
```bash
# Update Laravel and dependencies monthly
composer update --with-all-dependencies

# Update npm packages
npm update

# Update system packages
sudo apt update && sudo apt upgrade
```

### Disaster Recovery Plan

**Recovery Procedures**:
1. **Database Corruption**:
   - Restore from most recent daily backup
   - Verify invoice number sequence integrity
   - Test critical workflows

2. **File Loss (PDFs)**:
   - Restore from off-site backup (S3)
   - Regenerate missing PDFs if possible
   - Verify all invoices have PDF snapshots

3. **Complete Server Failure**:
   - Provision new server
   - Install dependencies
   - Restore database and files from backups
   - Update DNS if needed
   - Test all functionality

**Testing Recovery**:
- Perform test restore quarterly
- Document recovery time objective (RTO): < 4 hours
- Document recovery point objective (RPO): < 24 hours
