<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MariaDB/MySQL with explicit_defaults_for_timestamp=OFF gives the first TIMESTAMP
 * column of a table "ON UPDATE CURRENT_TIMESTAMP". For login_codes that was
 * expires_at, so counting a wrong attempt reset the expiry to "now" and the
 * correct code was then rejected as expired. Make it a plain nullable timestamp.
 */
class FixLoginCodesExpiresAt extends Migration
{
    public function up()
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE login_codes MODIFY expires_at TIMESTAMP NULL DEFAULT NULL');
        }
    }

    public function down()
    {
        // Intentionally left as is: the old behaviour was a bug.
    }
}
