<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixArticlesCollationForAccentSearch extends Migration
{
    /**
     * Run the migrations.
     * Fix collation from latin1_general_ci to utf8mb4_unicode_ci
     * for accent-insensitive search
     *
     * @return void
     */
    public function up()
    {
        // Modifier la collation des colonnes title et content
        DB::statement('ALTER TABLE articles 
            MODIFY title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            MODIFY content TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');
        
        // Modifier la collation de la table elle-même
        DB::statement('ALTER TABLE articles 
            CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revenir à la collation d'origine si nécessaire
        DB::statement('ALTER TABLE articles 
            MODIFY title VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_general_ci,
            MODIFY content TEXT CHARACTER SET latin1 COLLATE latin1_general_ci
        ');
        
        DB::statement('ALTER TABLE articles 
            CONVERT TO CHARACTER SET latin1 COLLATE latin1_general_ci
        ');
    }
}
