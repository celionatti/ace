<?php
// Usage Example

// 1. First set up the database connection
$pdo = new \PDO('mysql:host=localhost;dbname=test', 'username', 'password');
// Or for SQLite: $pdo = new \PDO('sqlite:database.sqlite');
Schema::setConnection($pdo);

// 2. Create a users table
Schema::create('users', function (Blueprint $table) {
    $table->id(); // Creates auto-incrementing primary key 'id'
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->boolean('is_admin')->default(false);
    $table->timestamps(); // Adds created_at and updated_at datetime columns

    // Add index on name column
    $table->index('name');
});

// 3. Create a roles table with foreign key
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
});

// 4. Modify users table to add relationship
Schema::table('users', function (Blueprint $table) {
    // Add new column
    $table->integer('age')->unsigned()->nullable()->after('name');

    // Add index on age column
    $table->index('age', 'users_age_index');

    // Add foreign key constraint
    $table->foreign('role_id')
          ->references('id')
          ->on('roles')
          ->onDelete('CASCADE');

    // Drop a column
    $table->dropColumn('age');

    // Drop an index
    $table->dropIndex('users_age_index');

    // Drop foreign key
    $table->dropForeign('users_role_id_foreign');
});

// 5. Drop tables
Schema::dropIfExists('users');
Schema::dropIfExists('roles');

// 6. Check table existence
if (Schema::hasTable('posts')) {
    echo "Posts table exists!";
}

// 7. Check column existence
if (Schema::hasColumn('users', 'email')) {
    echo "Users table has email column!";
}