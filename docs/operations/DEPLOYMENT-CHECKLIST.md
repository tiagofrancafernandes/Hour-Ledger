# V1 Deployment Checklist

**Date**: 2026-07-04  
**Version**: 1.0.0  
**Environment**: Staging → Production  
**Status**: Ready for Deployment Execution

---

## Pre-Deployment Phase (24 hours before)

### Infrastructure Validation

- [ ] **Database Server**
  - [ ] PostgreSQL 13+ installed and running
  - [ ] Required extensions enabled: `uuid-ossp`, `pgcrypto`
  - [ ] Database user with appropriate permissions created
  - [ ] Backup strategy configured and tested
  - [ ] Connection pooling configured (PgBouncer or equivalent)

- [ ] **Application Server**
  - [ ] PHP 8.1+ with required extensions: `php-pgsql`, `php-redis`, `php-curl`, `php-json`
  - [ ] Composer dependencies can be installed
  - [ ] Node.js available for frontend assets (v18+)
  - [ ] Disk space available: minimum 10GB
  - [ ] Memory available: minimum 4GB for PHP-FPM

- [ ] **Networking & Security**
  - [ ] SSL certificate installed and valid
  - [ ] Firewall rules configured
  - [ ] Database connection secured (SSL/TLS)
  - [ ] Environment variables protected (no secrets in code)
  - [ ] API rate limiting configured

- [ ] **Monitoring & Logging**
  - [ ] Logging directory writable and with retention policy
  - [ ] Error tracking service integrated (if applicable)
  - [ ] Performance monitoring configured
  - [ ] Uptime monitoring configured
  - [ ] Alert channels configured

### Code Preparation

- [ ] **Version Control**
  - [ ] All changes committed to repository
  - [ ] Git tag created: `v1.0.0`
  - [ ] Branch protection rules verified
  - [ ] Release branch created if needed

- [ ] **Build & Dependency Check**
  - [ ] `composer install` runs without errors
  - [ ] All dependencies are compatible
  - [ ] No security vulnerabilities in dependencies
  - [ ] `composer audit` passes with no issues
  - [ ] Autoloader optimized: `composer dump-autoload -o`

- [ ] **Configuration**
  - [ ] `.env.production` created with all required variables
  - [ ] `APP_KEY` generated: `php artisan key:generate`
  - [ ] `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` set correctly
  - [ ] `MAIL_*` variables configured
  - [ ] `REDIS_HOST` and `REDIS_PASSWORD` set (if using Redis)
  - [ ] `APP_DEBUG` set to `false`
  - [ ] `APP_ENV` set to `production`

- [ ] **Documentation Review**
  - [ ] Deployment runbook reviewed
  - [ ] Database schema documentation current
  - [ ] API documentation generated and accessible
  - [ ] Troubleshooting guide prepared

---

## Deployment Phase (Maintenance Window)

### Pre-Deployment Backup

- [ ] **Database Backup**
  - [ ] Full database backup created: `pg_dump <database> > backup.sql.gz`
  - [ ] Backup verified and restorable
  - [ ] Backup size noted for recovery time estimation
  - [ ] Backup location secured (off-site if possible)

- [ ] **Application Backup**
  - [ ] Current application code backed up
  - [ ] Current configuration backed up
  - [ ] Assets backed up (if any)

- [ ] **Notification**
  - [ ] Stakeholders notified of maintenance window
  - [ ] Maintenance mode message prepared
  - [ ] Support team on standby

### Database Migration Execution

- [ ] **Pre-Migration Validation**
  - [ ] All 42 migrations reviewed
  - [ ] Migration names documented and ordered
  - [ ] No dependent migrations failing
  - [ ] Database state verified

- [ ] **Migration Execution**
  - [ ] Enable maintenance mode: `php artisan down`
  - [ ] Run migrations: `php artisan migrate --force`
  - [ ] Verify all migrations executed successfully
  - [ ] Check for any errors in migration output
  - [ ] Confirm database schema matches expected state

- [ ] **Data Validation Post-Migration**
  - [ ] All tables created with correct structure
  - [ ] Indexes created as expected
  - [ ] Foreign key constraints applied
  - [ ] Soft delete columns present in all relevant tables
  - [ ] Tenant isolation verified at database level

### Application Deployment

- [ ] **Code Deployment**
  - [ ] Pull latest code: `git pull origin v1.0.0`
  - [ ] Install dependencies: `composer install --no-dev --optimize-autoloader`
  - [ ] Clear caches: `php artisan cache:clear`
  - [ ] Clear config cache: `php artisan config:cache`
  - [ ] Clear route cache: `php artisan route:cache`
  - [ ] Publish assets: `php artisan publish:assets`

- [ ] **Service Cache & Configuration**
  - [ ] Config cache rebuilt: `php artisan config:cache`
  - [ ] Route cache rebuilt: `php artisan route:cache`
  - [ ] View cache cleared: `php artisan view:clear`
  - [ ] Event cache refreshed: `php artisan event:cache`

- [ ] **Application Start**
  - [ ] Application server started: `php artisan serve` or `php-fpm restart`
  - [ ] Worker processes (if any) started
  - [ ] Queue workers started (if applicable)
  - [ ] Cron jobs verified and active
  - [ ] Log output monitored for errors

- [ ] **Disable Maintenance Mode**
  - [ ] Verify application is responsive before disabling
  - [ ] Exit maintenance mode: `php artisan up`
  - [ ] Confirm users can access application

---

## Immediate Post-Deployment Validation

### API Endpoint Validation

Test each critical endpoint with valid request:

- [ ] **Authentication**
  - [ ] `POST /api/auth/register` - Returns 201/422 as expected
  - [ ] `POST /api/auth/login` - Returns 200 with token
  - [ ] `POST /api/auth/logout` - Returns 204/401 appropriately

- [ ] **Instructor Management**
  - [ ] `GET /api/instructors` - Returns instructor list
  - [ ] `POST /api/instructors` - Creates new instructor
  - [ ] `GET /api/instructors/{id}` - Returns instructor details
  - [ ] `PUT /api/instructors/{id}` - Updates instructor

- [ ] **Student Links**
  - [ ] `GET /api/student-links` - Returns links
  - [ ] `POST /api/student-links` - Creates new link
  - [ ] `DELETE /api/student-links/{id}` - Removes link

- [ ] **Invitations**
  - [ ] `POST /api/invitations` - Sends invitation
  - [ ] `POST /api/invitations/{token}/accept` - Accepts invitation
  - [ ] `POST /api/invitations/{token}/reject` - Rejects invitation

- [ ] **Package Management**
  - [ ] `GET /api/packages` - Lists packages
  - [ ] `POST /api/packages` - Creates package
  - [ ] `PUT /api/packages/{id}` - Updates package
  - [ ] `DELETE /api/packages/{id}` - Soft-deletes package

- [ ] **Hour Acquisition**
  - [ ] `POST /api/package-purchases` - Purchases package
  - [ ] `GET /api/package-purchases` - Lists purchases
  - [ ] `GET /api/wallet/balance` - Returns wallet balance
  - [ ] `GET /api/ledger/entries` - Lists transactions

- [ ] **Lesson Scheduling**
  - [ ] `POST /api/lessons` - Creates lesson
  - [ ] `GET /api/lessons` - Lists lessons
  - [ ] `PUT /api/lessons/{id}/consume` - Consumes hours
  - [ ] `DELETE /api/lessons/{id}` - Cancels lesson

### Database Integrity Validation

- [ ] **Schema Verification**
  - [ ] All 11 core tables created
  - [ ] All foreign keys active
  - [ ] Soft-delete columns present
  - [ ] Tenant_id columns populated with constraints
  - [ ] Indexes created successfully

- [ ] **Data Integrity**
  - [ ] Users table contains valid data
  - [ ] Tenants table properly configured
  - [ ] No orphaned records (foreign key violations)
  - [ ] Soft-deleted records properly flagged
  - [ ] Timestamps (created_at, updated_at) present

- [ ] **Multi-Tenant Isolation**
  - [ ] Query two different tenants, verify no data leakage
  - [ ] Create resource in tenant A, verify not visible to tenant B
  - [ ] Test tenant context switching works correctly
  - [ ] Soft-deleted records not visible in normal queries

### Test Suite Verification

- [ ] **Phase 3 Tests (Multi-Instructor)**
  - [ ] Run: `php artisan test --filter="MultiInstructor"`
  - [ ] Result: 16/16 tests passing ✅
  - [ ] Duration acceptable (<3s)

- [ ] **Phase 4 Tests (Multi-Tenancy Security)**
  - [ ] Run: `php artisan test --filter="MultiTenancy"`
  - [ ] Result: 63/63 tests passing ✅
  - [ ] Duration acceptable (<10s)

- [ ] **Full Test Suite**
  - [ ] Run: `php artisan test`
  - [ ] Result: All tests passing
  - [ ] Coverage acceptable (>80%)

### Performance Validation

- [ ] **Response Times**
  - [ ] Login endpoint: <200ms
  - [ ] Package list: <300ms
  - [ ] Wallet balance: <150ms
  - [ ] Purchase transaction: <500ms (including ledger)
  - [ ] Lesson consumption: <400ms

- [ ] **Load Testing**
  - [ ] 50 concurrent users: no errors
  - [ ] 100 concurrent users: response time acceptable
  - [ ] Database connections stable
  - [ ] Memory usage within limits

- [ ] **Error Handling**
  - [ ] 404 errors return proper response
  - [ ] 401 errors on unauthenticated access
  - [ ] 403 errors on unauthorized access
  - [ ] 500 errors logged properly
  - [ ] Rate limiting working (if configured)

### Security Validation

- [ ] **Authentication & Authorization**
  - [ ] Invalid credentials rejected
  - [ ] Expired tokens rejected
  - [ ] Cross-tenant requests blocked
  - [ ] Permission checks enforced
  - [ ] SQL injection prevention verified

- [ ] **Data Protection**
  - [ ] Sensitive data not logged
  - [ ] Passwords hashed (bcrypt)
  - [ ] Database credentials not exposed
  - [ ] No test/debug endpoints in production
  - [ ] HTTPS enforced

- [ ] **API Security**
  - [ ] CORS configured correctly
  - [ ] CSRF protection active
  - [ ] Rate limiting working
  - [ ] Input validation enforced
  - [ ] Output encoding applied

### Logging & Monitoring

- [ ] **Application Logs**
  - [ ] Log file created and writing
  - [ ] No error stack traces in production logs
  - [ ] Log rotation configured
  - [ ] Error rate acceptable (<0.01%)

- [ ] **Monitoring Alerts**
  - [ ] CPU usage monitored
  - [ ] Memory usage monitored
  - [ ] Disk usage monitored
  - [ ] Database connection pool monitored
  - [ ] Error rate monitored
  - [ ] Response time monitored

---

## Post-Deployment Phase (48 hours after)

### Extended Monitoring

- [ ] **System Health**
  - [ ] Uptime stable (no crashes)
  - [ ] Error rate remaining low
  - [ ] Response times consistent
  - [ ] Resource usage stable
  - [ ] Database queries performing well

- [ ] **User Access Logs**
  - [ ] Review access patterns
  - [ ] Check for any suspicious activity
  - [ ] Verify all user types can access features
  - [ ] Confirm no authentication issues

- [ ] **Data Consistency**
  - [ ] Verify data consistency across multiple queries
  - [ ] Check for any orphaned records
  - [ ] Validate all transactions recorded in ledger
  - [ ] Confirm soft-delete integrity

### Issues Tracking

- [ ] **Bug Report System**
  - [ ] Bug tracking system configured
  - [ ] Support team notified of reporting channel
  - [ ] Critical issues response time SLA defined
  - [ ] Known issues documented

- [ ] **Performance Analysis**
  - [ ] Slow queries identified and optimized if needed
  - [ ] Database query patterns reviewed
  - [ ] Cache hit rates analyzed
  - [ ] Performance improvements documented

### Documentation Updates

- [ ] **Runbook Updates**
  - [ ] Deployment runbook updated with actual timings
  - [ ] Rollback procedure verified and documented
  - [ ] Incident response procedures confirmed
  - [ ] Support team documentation updated

- [ ] **Release Notes**
  - [ ] Release notes published
  - [ ] Changelog updated
  - [ ] Feature documentation distributed to users
  - [ ] API documentation accessible

---

## Rollback Plan (If Needed)

### Rollback Trigger Criteria

Rollback initiated if any of the following occur within 1 hour of production deployment:

- [ ] Database migration fails or is partially executed
- [ ] >5% of API requests returning errors
- [ ] Authentication system failure
- [ ] Multi-tenant isolation breach detected
- [ ] Complete application unavailability
- [ ] Data corruption detected

### Rollback Procedure

1. **Immediate Actions**
   - [ ] Enable maintenance mode: `php artisan down`
   - [ ] Stop all worker processes
   - [ ] Notify stakeholders

2. **Application Rollback**
   - [ ] `git checkout <previous-stable-tag>`
   - [ ] `composer install --no-dev`
   - [ ] `php artisan cache:clear`
   - [ ] Clear all cache: `redis-cli flushall` (if using Redis)

3. **Database Rollback**
   - [ ] Stop all database operations
   - [ ] Restore from backup: `psql <database> < backup.sql`
   - [ ] Verify database integrity
   - [ ] Confirm data consistency

4. **Service Restart**
   - [ ] Restart PHP-FPM: `systemctl restart php-fpm`
   - [ ] Restart application server
   - [ ] Restart queue workers
   - [ ] Verify services healthy

5. **Exit Maintenance Mode**
   - [ ] `php artisan up`
   - [ ] Verify application responding correctly
   - [ ] Notify stakeholders of rollback completion

6. **Post-Rollback**
   - [ ] Investigate root cause
   - [ ] Document incident
   - [ ] Plan remediation
   - [ ] Schedule retry with fixes

---

## Communication Plan

### Pre-Deployment (24 hours before)

- [ ] **Email Notification**
  - [ ] Subject: "Hour Ledger V1 Production Deployment - 2026-07-07"
  - [ ] Include: Maintenance window duration, expected impact
  - [ ] Recipients: Stakeholders, support team, users (if applicable)

### During Deployment

- [ ] **Maintenance Page**
  - [ ] "System under maintenance, expected to be back online at [TIME]"
  - [ ] Estimated maintenance duration displayed
  - [ ] Contact information for urgent issues

### Post-Deployment (6 hours after)

- [ ] **Success Notification**
  - [ ] V1 successfully deployed to production
  - [ ] Link to release notes
  - [ ] Feature highlights
  - [ ] Support contact information

---

## Sign-Off & Approval

| Role | Name | Date | Signature |
|------|------|------|-----------|
| **Deployment Lead** | | | |
| **Infrastructure** | | | |
| **Database Admin** | | | |
| **QA Lead** | | | |
| **Operations Manager** | | | |

---

## References

- **Completion Report**: `docs/agent/reports/V1-COMPLETION-REPORT.md`
- **Features Summary**: `docs/product/V1-FEATURES-SUMMARY.md`
- **API Documentation**: (Generated during deployment)
- **Database Schema**: `docs/database/schema.md`
- **Troubleshooting Guide**: `docs/operations/TROUBLESHOOTING.md` (to be created)

---

**Version**: 1.0  
**Last Updated**: 2026-07-04  
**Status**: Ready for Execution  
**Next Review**: Post-deployment
