<?php

/*
| Role & permission map for GymStack.
| super_admin bypasses every check (see AppServiceProvider Gate::before).
*/

return [
    'roles' => [
        'super_admin' => 'Super Admin',
        'gym_admin' => 'Gym Admin',
        'manager' => 'Manager',
        'receptionist' => 'Receptionist',
        'accountant' => 'Accountant',
        'trainer' => 'Trainer',
        'member' => 'Member',
    ],

    // Roles a gym admin may assign to staff accounts.
    'staff_roles' => ['gym_admin', 'manager', 'receptionist', 'accountant', 'trainer'],

    'permissions' => [
        'dashboard.view' => ['gym_admin', 'manager', 'receptionist', 'accountant', 'trainer'],
        'gym.settings' => ['gym_admin'],
        'branches.manage' => ['gym_admin'],
        'staff.manage' => ['gym_admin'],
        'activity.view' => ['gym_admin', 'manager'],

        'members.view' => ['gym_admin', 'manager', 'receptionist', 'accountant', 'trainer'],
        'members.manage' => ['gym_admin', 'manager', 'receptionist'],
        'members.delete' => ['gym_admin'],
        'plans.manage' => ['gym_admin', 'manager'],
        'memberships.manage' => ['gym_admin', 'manager', 'receptionist'],
        'attendance.manage' => ['gym_admin', 'manager', 'receptionist', 'trainer'],

        'billing.view' => ['gym_admin', 'manager', 'receptionist', 'accountant'],
        'billing.collect' => ['gym_admin', 'manager', 'receptionist', 'accountant'],
        'billing.refund' => ['gym_admin', 'accountant'],
        'finance.view' => ['gym_admin', 'accountant'],
        'expenses.manage' => ['gym_admin', 'accountant', 'manager'],

        'trainers.manage' => ['gym_admin', 'manager'],
        'exercises.manage' => ['gym_admin', 'manager', 'trainer'],

        // Personal training
        'pt.packages' => ['gym_admin', 'manager'],
        'pt.enroll' => ['gym_admin', 'manager', 'receptionist'],
        'pt.schedule' => ['gym_admin', 'manager', 'receptionist', 'trainer'],
        'pt.coach' => ['gym_admin', 'manager', 'trainer'], // assessments, workouts, nutrition, goals
        'pt.photos' => ['gym_admin', 'trainer'], // trainer is further limited to own clients by policy
        'reminders.send' => ['gym_admin', 'manager', 'receptionist'],
        'pt.all_clients' => ['gym_admin', 'manager', 'receptionist'], // see every PT client, not just own
    ],

    'billing_cycles' => [
        'monthly' => ['label' => 'Monthly', 'days' => 30],
        'quarterly' => ['label' => 'Quarterly', 'days' => 90],
        'half_yearly' => ['label' => 'Half-yearly', 'days' => 180],
        'yearly' => ['label' => 'Yearly', 'days' => 365],
        'custom' => ['label' => 'Custom', 'days' => null],
    ],

    'payment_methods' => [
        'cash' => 'Cash', 'card' => 'Card', 'upi' => 'UPI', 'bank_transfer' => 'Bank transfer', 'online' => 'Online',
    ],

    'expense_categories' => [
        'rent' => 'Rent', 'salary' => 'Salaries', 'utilities' => 'Utilities', 'equipment' => 'Equipment',
        'maintenance' => 'Maintenance', 'marketing' => 'Marketing', 'supplements' => 'Supplements', 'other' => 'Other',
    ],

    'body_metrics' => [
        'weight' => ['label' => 'Weight', 'unit' => 'kg', 'better' => 'down'],
        'bmi' => ['label' => 'BMI', 'unit' => '', 'better' => 'down'],
        'body_fat' => ['label' => 'Body fat', 'unit' => '%', 'better' => 'down'],
        'muscle_mass' => ['label' => 'Muscle mass', 'unit' => 'kg', 'better' => 'up'],
        'chest' => ['label' => 'Chest', 'unit' => 'cm', 'better' => 'neutral'],
        'waist' => ['label' => 'Waist', 'unit' => 'cm', 'better' => 'down'],
        'hip' => ['label' => 'Hip', 'unit' => 'cm', 'better' => 'down'],
        'shoulder' => ['label' => 'Shoulders', 'unit' => 'cm', 'better' => 'up'],
        'neck' => ['label' => 'Neck', 'unit' => 'cm', 'better' => 'neutral'],
        'biceps' => ['label' => 'Biceps', 'unit' => 'cm', 'better' => 'up'],
        'forearm' => ['label' => 'Forearms', 'unit' => 'cm', 'better' => 'up'],
        'thigh' => ['label' => 'Thighs', 'unit' => 'cm', 'better' => 'neutral'],
        'calf' => ['label' => 'Calves', 'unit' => 'cm', 'better' => 'up'],
    ],

    'fitness_ratings' => [
        'strength' => 'Strength', 'flexibility' => 'Flexibility', 'mobility' => 'Mobility',
        'endurance' => 'Endurance', 'cardio' => 'Cardio fitness', 'balance' => 'Balance',
        'posture' => 'Posture', 'functional_movement' => 'Functional movement',
    ],

    'fitness_tests' => [
        'pushups' => ['label' => 'Push-up test', 'unit' => 'reps', 'higher_is_better' => true],
        'squats' => ['label' => 'Squat test (1 min)', 'unit' => 'reps', 'higher_is_better' => true],
        'plank' => ['label' => 'Plank hold', 'unit' => 'sec', 'higher_is_better' => true],
        'run_1km' => ['label' => '1 km run', 'unit' => 'min', 'higher_is_better' => false],
        'vo2max' => ['label' => 'VO₂ max (est.)', 'unit' => 'ml/kg/min', 'higher_is_better' => true],
        'grip' => ['label' => 'Grip strength', 'unit' => 'kg', 'higher_is_better' => true],
        'sit_reach' => ['label' => 'Sit & reach', 'unit' => 'cm', 'higher_is_better' => true],
    ],

    'goal_types' => [
        'weight_loss' => 'Weight loss', 'muscle_gain' => 'Muscle gain', 'recomposition' => 'Body recomposition',
        'strength' => 'Strength', 'endurance' => 'Endurance', 'fitness' => 'Fitness improvement',
    ],

    'meal_types' => [
        'breakfast' => 'Breakfast', 'morning_snack' => 'Morning snack', 'lunch' => 'Lunch',
        'pre_workout' => 'Pre-workout', 'post_workout' => 'Post-workout', 'dinner' => 'Dinner',
    ],

    'photo_angles' => ['front' => 'Front', 'back' => 'Back', 'left' => 'Left side', 'right' => 'Right side'],

    'weekdays' => [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'],

    // Whether a no-show consumes one PT session from the package.
    'no_show_consumes_session' => true,

    'otp' => ['length' => 6, 'ttl_minutes' => 10, 'max_attempts' => 5],
];
