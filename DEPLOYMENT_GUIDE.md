# HRMS Deployment Guide for cPanel

## 📋 Pre-Deployment Checklist

✅ **Database Configuration Updated**
- Database Name: `crmsoftware`
- Database User: `crmsoftware`
- Database Password: `crmsoftware@123`
- Production environment configured

✅ **Files Configured**
- `.env` file created with production settings
- `config.php` updated with database credentials
- CORS configured for `https://whatsapp.proschool360.com`

## 🚀 Deployment Steps

### 1. Upload Files to cPanel

1. **Compress the backend folder** into a ZIP file
2. **Login to cPanel** at your hosting provider
3. **Navigate to File Manager**
4. **Upload the ZIP file** to your domain's public_html directory
5. **Extract the files** in the correct location

### 2. Database Setup

1. **Access MySQL Databases** in cPanel
2. **Verify database exists**: `crmsoftware`
3. **Import database schema**:
   - Go to phpMyAdmin
   - Select `crmsoftware` database
   - Import `database/schema.sql`
   - Import `database/sample_data.sql` (optional)

### 3. Composer Dependencies

**Run in cPanel Terminal or SSH:**
```bash
cd /path/to/your/backend/directory
composer install --no-dev --optimize-autoloader
```

### 4. File Permissions

Set proper permissions:
```bash
chmod 755 backend/
chmod 644 backend/.env
chmod 755 backend/uploads/
chmod 755 backend/logs/
```

### 5. Create Required Directories

```bash
mkdir -p backend/uploads/profiles
mkdir -p backend/uploads/documents
mkdir -p backend/uploads/resumes
mkdir -p backend/logs
```

## 🔧 Configuration Details

### Database Connection
- **Host**: localhost
- **Database**: crmsoftware
- **Username**: crmsoftware
- **Password**: crmsoftware@123
- **Port**: 3306

### API Endpoints
Base URL: `https://whatsapp.proschool360.com/api/`

### Security Settings
- Environment: Production
- Debug: Disabled
- CORS: Restricted to production domain
- JWT Secret: Updated for production

## 🧪 Testing After Deployment

### 1. Test Database Connection
Access: `https://whatsapp.proschool360.com/api/test-db`

### 2. Test Authentication
```bash
curl -X POST https://whatsapp.proschool360.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@hrms.com","password":"password"}'
```

### 3. Test CORS
Make sure frontend can connect to the API from the production domain.

## 📁 File Structure on Server

```
public_html/
├── backend/
│   ├── .env
│   ├── composer.json
│   ├── vendor/
│   ├── api/
│   ├── config/
│   ├── controllers/
│   ├── utils/
│   ├── uploads/
│   └── logs/
├── frontend/ (if hosting frontend on same domain)
└── database/
```

## 🔒 Security Recommendations

1. **Change JWT Secret**: Update to a more secure random string
2. **Enable HTTPS**: Ensure SSL certificate is installed
3. **Restrict File Access**: Add .htaccess rules
4. **Database Security**: Use strong passwords
5. **Regular Backups**: Set up automated backups

## 🐛 Troubleshooting

### Common Issues:

1. **Database Connection Failed**
   - Verify database credentials in `.env`
   - Check if database exists in cPanel
   - Ensure database user has proper permissions

2. **Composer Dependencies Missing**
   - Run `composer install` in backend directory
   - Check PHP version compatibility (>=7.4)

3. **File Permission Errors**
   - Set proper permissions for uploads and logs directories
   - Ensure web server can write to these directories

4. **CORS Errors**
   - Verify CORS_ALLOWED_ORIGINS in config
   - Check if frontend domain matches configuration

## 📞 Support

If you encounter issues:
1. Check error logs in `backend/logs/`
2. Verify all configuration files
3. Test database connection separately
4. Ensure all dependencies are installed

---

**Note**: Remember to keep your `.env` file secure and never commit it to version control with production credentials.