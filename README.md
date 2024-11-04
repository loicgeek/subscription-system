# Ntech Services Subscription System

A powerful and flexible subscription management system for Laravel applications, designed to handle subscription plans, payments, and user management.

## Table of Contents
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Commands](#commands)
- [License](#license)

## Requirements

- PHP 8.0 or higher
- Laravel 8.0 or higher
- Composer

## Installation

To install the `ntech-services/subscription-system` package, follow these steps:

1. **Install the package via Composer:**

   Run the following command in your terminal:

  `composer require ntech-services/subscription-system`

  
2. **Register Service Provider**

After installing the package, you should register the service provider in the `bootstrap/providers.php` file. Open `bootstrap/providers.php` and add the following line to the array:

```
[
    App\Providers\AppServiceProvider::class,
    NtechServices\SubscriptionSystem\SubscriptionServiceProvider::class, // add this line to providers array
];
```

3. **Publish the package configuration:**

   Publish the configuration file to customize the package settings.

  `php artisan vendor:publish --provider="NtechServices\SubscriptionSystem\SubscriptionSystemServiceProvider"`

4. **Run the migrations:**

   Migrate the subscription system tables to your database by executing:

  `php artisan migrate:subscription-system`

5. **Configure your models:**

   Add the `HasSubscriptions` trait to your user or model class that will handle subscriptions:

  ```
   use NtechServices\SubscriptionSystem\Traits\HasSubscriptions;

   class User extends Authenticatable
   {
       use HasSubscriptions;
   }
  ```

6. **Set up your environment variables:**

   Update your `.env` file with necessary configurations for the subscription system (if applicable).

## Configuration

After publishing the configuration file, you can find it at `Config/subscription.php`. Customize the settings according to your application's requirements.

## Usage

### Subscribing to a Plan

To subscribe a user to a plan, you can use the `subscribeToPlan` method provided by the `HasSubscriptions` trait:

```
$user = User::find(1);
$planPrice = PlanPrice::find(1); // Retrieve the desired plan

$user->subscribeToPlan($planPrice, $couponCode = null);
```

### Managing Subscriptions

You can manage subscriptions using the available methods in the `HasSubscriptions` trait:

- Check active subscriptions: `$user->activeSubscriptions()`
- Cancel subscription: `$subscription->cancelSubscription($softCancel = true)`
- Update subscription plan: `$subscription->updateSubscription($newPlanPrice)`

### Subscription History

Subscription status changes are automatically recorded in the subscription history for tracking purposes.

## Commands

### Migrate Subscription System Tables

To migrate the subscription system tables, you can run the following command:

`php artisan migrate:subscription-system`

## License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
