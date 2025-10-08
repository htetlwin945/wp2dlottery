<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Creates custom user roles and capabilities.
 */
function custom_lottery_add_roles() {
    // Define a custom capability for data entry
    $capability = 'enter_lottery_numbers';

    // Add Lottery Manager role
    add_role(
        'lottery_manager',
        __('Lottery Manager', 'custom-lottery'),
        [
            'read' => true,
            'manage_options' => true, // Gives access to all lottery pages
            $capability => true,
        ]
    );

    // Add Data Entry Clerk role
    add_role(
        'data_entry_clerk',
        __('Data Entry Clerk', 'custom-lottery'),
        [
            'read' => true,
            $capability => true, // Only gives access to the entry page
        ]
    );

    // Add Commission Agent role
    add_role(
        'commission_agent',
        __('Commission Agent', 'custom-lottery'),
        [
            'read' => true,
            $capability => true,
        ]
    );

    // Add Cover Agent role
    add_role(
        'cover_agent',
        __('Cover Agent', 'custom-lottery'),
        [
            'read' => true,
            $capability => true,
        ]
    );

    // Add the custom capability to the Administrator role as well
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap($capability);
    }
}

/**
 * Removes custom user roles and capabilities.
 */
function custom_lottery_remove_roles() {
    // Remove the custom capability from the Administrator role
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->remove_cap('enter_lottery_numbers');
    }

    // Remove the custom roles
    remove_role('lottery_manager');
    remove_role('data_entry_clerk');
    remove_role('commission_agent');
    remove_role('cover_agent');
}