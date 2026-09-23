<?php

# If you are having problems connecting to the MySQL database and all of the variables below are correct
# try changing the 'db_server' variable from localhost to 10.10.0.11. Fixes a problem due to sockets.
#   Thanks to @digininja for the fix.

# Database management system to use
$DBMS = 'MySQL';
#$DBMS = 'PGSQL'; // Currently disabled

# Load environment configuration from .env if present (Single Source of Truth)
$env_file = __DIR__ . '/.env';
$env = (file_exists($env_file)) ? @parse_ini_file($env_file) : [];

# Database variables
#   WARNING: The database specified under db_database WILL BE ENTIRELY DELETED during setup.
#   Please use a database dedicated to DVWA.
#
# If you are using MariaDB then you cannot use root, you must use create a dedicated DVWA user.
#   See README.md for more information on this.
$_DVWA = array();
$_DVWA[ 'db_server' ]   = isset($env['DB_HOST']) ? $env['DB_HOST'] : '10.10.0.11';
$_DVWA[ 'db_database' ] = isset($env['DB_NAME']) ? $env['DB_NAME'] : 'dvwa';
$_DVWA[ 'db_user' ]     = isset($env['DB_USER']) ? $env['DB_USER'] : 'dvwa';
$_DVWA[ 'db_password' ] = isset($env['DB_PASSWORD']) ? $env['DB_PASSWORD'] : 'p@ssw0rd';

# Only used with PostgreSQL/PGSQL database selection.
$_DVWA[ 'db_port '] = '5432';

# ReCAPTCHA settings
#   Used for the 'Insecure CAPTCHA' module
#   You'll need to generate your own keys at: https://www.google.com/recaptcha/admin/create
$_DVWA[ 'recaptcha_public_key' ]  = '';
$_DVWA[ 'recaptcha_private_key' ] = '';

# Default security level
#   Default value for the secuirty level with each session.
#   The default is 'impossible'. You may wish to set this to either 'low', 'medium', 'high' or impossible'.
$_DVWA[ 'default_security_level' ] = isset($env['DEFAULT_SECURITY_LEVEL']) ? $env['DEFAULT_SECURITY_LEVEL'] : 'low';

# Default PHPIDS status
#   PHPIDS status with each session.
#   The default is 'disabled'. You can set this to be either 'enabled' or 'disabled'.
$_DVWA[ 'default_phpids_level' ] = 'disabled';

# Verbose PHPIDS messages
#   Enabling this will show why the WAF blocked the request on the blocked request.
#   The default is 'disabled'. You can set this to be either 'true' or 'false'.
$_DVWA[ 'default_phpids_verbose' ] = 'false';

?>
