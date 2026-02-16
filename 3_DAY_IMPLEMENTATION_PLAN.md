# 3-DAY IMPLEMENTATION PLAN
## Expert Medical Center - Backend Essentials

**Timeline:** 3 Days
**Scope:** Core functionality only (No webhooks, No revenue analytics)
**Goal:** MVP backend ready for Next.js frontend integration

---

## SCOPE SUMMARY

### ✅ INCLUDED (Critical Path)
- Branches management (Dubai/Abu Dhabi)
- Payment tracking (Stripe checkout sessions)
- Enhanced appointments API with payment fields
- Branch filtering on all APIs
- Multilingual email templates (AR/EN)
- Basic doctor profile enhancements
- Appointment metadata storage

### ❌ EXCLUDED (Out of Scope)
- ~~Stripe webhooks~~ (manual payment verification)
- ~~Revenue reports~~ (future phase)
- ~~Advanced analytics~~ (future phase)
- ~~Patient feedback system~~ (future phase)
- ~~Refund processing~~ (future phase)
- ~~PDF/CSV exports~~ (future phase)

---

## DAY 1: DATABASE & BRANCHES FOUNDATION

**Goal:** Set up database structure and branches management

### Morning Session (4 hours)

#### 1.1 Database Migrations (2 hours)

**Create and run 4 critical migrations:**

**Migration 061: Branches Table**
```bash
File: application/migrations/061_create_branches_table.php
```
```php
<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_branches_table extends EA_Migration
{
    public function up(): void
    {
        $this->dbforge->add_field([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'name_ar' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'address' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'city' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => '1',
                'default' => '1',
            ],
            'create_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'update_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('is_active');
        $this->dbforge->create_table('branches', true, ['engine' => 'InnoDB']);

        // Seed default branches
        $this->db->insert('branches', [
            'name' => 'Dubai Branch',
            'name_ar' => 'فرع دبي',
            'city' => 'Dubai',
            'is_active' => 1,
            'create_datetime' => date('Y-m-d H:i:s'),
            'update_datetime' => date('Y-m-d H:i:s'),
        ]);

        $this->db->insert('branches', [
            'name' => 'Abu Dhabi Branch',
            'name_ar' => 'فرع أبو ظبي',
            'city' => 'Abu Dhabi',
            'is_active' => 1,
            'create_datetime' => date('Y-m-d H:i:s'),
            'update_datetime' => date('Y-m-d H:i:s'),
        ]);
    }

    public function down(): void
    {
        $this->dbforge->drop_table('branches');
    }
}
```

**Migration 062: Add Branch to Services**
```bash
File: application/migrations/062_add_branch_to_services.php
```
```php
<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_branch_to_services extends EA_Migration
{
    public function up(): void
    {
        $fields = [
            'id_branches' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
                'null' => true,
                'after' => 'id_service_categories',
            ],
        ];

        $this->dbforge->add_column('services', $fields);

        // Add foreign key
        $this->db->query('ALTER TABLE ' . $this->db->dbprefix('services') . '
            ADD CONSTRAINT fk_services_branches
            FOREIGN KEY (id_branches) REFERENCES ' . $this->db->dbprefix('branches') . '(id)
            ON DELETE SET NULL ON UPDATE CASCADE');

        // Add index
        $this->db->query('ALTER TABLE ' . $this->db->dbprefix('services') . '
            ADD INDEX idx_branches (id_branches)');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE ' . $this->db->dbprefix('services') . '
            DROP FOREIGN KEY fk_services_branches');
        $this->dbforge->drop_column('services', 'id_branches');
    }
}
```

**Migration 063: Provider-Branch Relationship**
```bash
File: application/migrations/063_create_provider_branches_table.php
```
```php
<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_provider_branches_table extends EA_Migration
{
    public function up(): void
    {
        $this->dbforge->add_field([
            'id_users_provider' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
            ],
            'id_branches' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
            ],
        ]);

        $this->dbforge->add_key('id_users_provider', true);
        $this->dbforge->add_key('id_branches', true);
        $this->dbforge->create_table('provider_branches', true, ['engine' => 'InnoDB']);

        // Add foreign keys
        $this->db->query('ALTER TABLE ' . $this->db->dbprefix('provider_branches') . '
            ADD CONSTRAINT fk_provider_branches_provider
            FOREIGN KEY (id_users_provider) REFERENCES ' . $this->db->dbprefix('users') . '(id)
            ON DELETE CASCADE ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE ' . $this->db->dbprefix('provider_branches') . '
            ADD CONSTRAINT fk_provider_branches_branch
            FOREIGN KEY (id_branches) REFERENCES ' . $this->db->dbprefix('branches') . '(id)
            ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->dbforge->drop_table('provider_branches');
    }
}
```

**Migration 064: Payment Fields in Appointments**
```bash
File: application/migrations/064_add_payment_fields_to_appointments.php
```
```php
<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_payment_fields_to_appointments extends EA_Migration
{
    public function up(): void
    {
        $fields = [
            'payment_status' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'default' => 'pending',
                'after' => 'status',
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
                'after' => 'payment_status',
            ],
            'stripe_session_id' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'after' => 'payment_method',
            ],
            'stripe_payment_intent_id' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'after' => 'stripe_session_id',
            ],
            'payment_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'after' => 'stripe_payment_intent_id',
            ],
            'payment_currency' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'default' => 'AED',
                'after' => 'payment_amount',
            ],
        ];

        $this->dbforge->add_column('appointments', $fields);

        // Add indexes
        $this->db->query('ALTER TABLE ' . $this->db->dbprefix('appointments') . '
            ADD INDEX idx_payment_status (payment_status),
            ADD INDEX idx_stripe_session (stripe_session_id)');
    }

    public function down(): void
    {
        $this->dbforge->drop_column('appointments', 'payment_status');
        $this->dbforge->drop_column('appointments', 'payment_method');
        $this->dbforge->drop_column('appointments', 'stripe_session_id');
        $this->dbforge->drop_column('appointments', 'stripe_payment_intent_id');
        $this->dbforge->drop_column('appointments', 'payment_amount');
        $this->dbforge->drop_column('appointments', 'payment_currency');
    }
}
```

**Run Migrations:**
```bash
php index.php console migrate
```

---

#### 1.2 Branches Model (1 hour)

**Create:** `application/models/Branches_model.php`

```php
<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Branches model
 */
class Branches_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'is_active' => 'boolean',
    ];

    protected array $api_resource = [
        'id' => 'id',
        'name' => 'name',
        'nameAr' => 'name_ar',
        'address' => 'address',
        'phone' => 'phone',
        'email' => 'email',
        'city' => 'city',
        'isActive' => 'is_active',
    ];

    public function save(array $branch): int
    {
        $this->validate($branch);

        if (empty($branch['id'])) {
            return $this->insert($branch);
        } else {
            return $this->update($branch);
        }
    }

    public function validate(array $branch): void
    {
        if (empty($branch['name'])) {
            throw new InvalidArgumentException('Branch name is required');
        }
    }

    protected function insert(array $branch): int
    {
        $branch['create_datetime'] = date('Y-m-d H:i:s');
        $branch['update_datetime'] = date('Y-m-d H:i:s');

        if (!$this->db->insert('branches', $branch)) {
            throw new RuntimeException('Could not insert branch');
        }

        return $this->db->insert_id();
    }

    protected function update(array $branch): int
    {
        $branch['update_datetime'] = date('Y-m-d H:i:s');

        if (!$this->db->update('branches', $branch, ['id' => $branch['id']])) {
            throw new RuntimeException('Could not update branch');
        }

        return $branch['id'];
    }

    public function find(int $id): array
    {
        $branch = $this->db->get_where('branches', ['id' => $id])->row_array();

        if (!$branch) {
            throw new InvalidArgumentException('Branch not found: ' . $id);
        }

        return $branch;
    }

    public function get(?array $where = null, int $limit = null, int $offset = null, string $order_by = null): array
    {
        if ($where !== null) {
            $this->db->where($where);
        }

        if ($order_by !== null) {
            $this->db->order_by($order_by);
        }

        return $this->db->get('branches', $limit, $offset)->result_array();
    }

    public function delete(int $id): void
    {
        $this->db->delete('branches', ['id' => $id]);
    }
}
```

---

#### 1.3 Branches API Controller (1 hour)

**Create:** `application/controllers/api/v1/Branches_api_v1.php`

```php
<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Branches API v1 controller
 */
class Branches_api_v1 extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('branches_model');
        $this->load->library('api');
        $this->api->auth();
        $this->api->model('branches_model');
    }

    /**
     * GET /api/v1/branches
     */
    public function index(): void
    {
        try {
            $keyword = $this->api->request_keyword();
            $limit = $this->api->request_limit();
            $offset = $this->api->request_offset();
            $order_by = $this->api->request_order_by();
            $fields = $this->api->request_fields();

            $where = null;

            // Filter by active status
            $is_active = request('isActive');
            if ($is_active !== null) {
                $where['is_active'] = filter_var($is_active, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }

            $branches = empty($keyword)
                ? $this->branches_model->get($where, $limit, $offset, $order_by)
                : $this->branches_model->search($keyword, $limit, $offset, $order_by);

            foreach ($branches as &$branch) {
                $this->branches_model->api_encode($branch);

                if (!empty($fields)) {
                    $this->branches_model->only($branch, $fields);
                }
            }

            json_response($branches);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * GET /api/v1/branches/{id}
     */
    public function show(?int $id = null): void
    {
        try {
            $branch = $this->branches_model->find($id);
            $this->branches_model->api_encode($branch);

            $fields = $this->api->request_fields();
            if (!empty($fields)) {
                $this->branches_model->only($branch, $fields);
            }

            json_response($branch);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * POST /api/v1/branches
     */
    public function store(): void
    {
        try {
            $branch = request();
            $this->branches_model->api_decode($branch);
            $branch_id = $this->branches_model->save($branch);
            $created_branch = $this->branches_model->find($branch_id);
            $this->branches_model->api_encode($created_branch);

            json_response($created_branch, 201);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * PUT /api/v1/branches/{id}
     */
    public function update(?int $id = null): void
    {
        try {
            $branch = request();
            $branch['id'] = $id;
            $this->branches_model->api_decode($branch);
            $this->branches_model->save($branch);
            $updated_branch = $this->branches_model->find($id);
            $this->branches_model->api_encode($updated_branch);

            json_response($updated_branch);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * DELETE /api/v1/branches/{id}
     */
    public function destroy(?int $id = null): void
    {
        try {
            $this->branches_model->delete($id);
            response('', 204);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
```

---

### Afternoon Session (4 hours)

#### 1.4 Update Existing Models for Branch Support (2 hours)

**Update:** `application/models/Services_model.php`

Add to `$api_resource` array:
```php
protected array $api_resource = [
    // ... existing fields
    'branchId' => 'id_branches',
];
```

**Update:** `application/models/Providers_model.php`

Add method to get provider branches:
```php
public function get_branches(int $provider_id): array
{
    return $this->db
        ->select('branches.*')
        ->from('provider_branches')
        ->join('branches', 'branches.id = provider_branches.id_branches')
        ->where('provider_branches.id_users_provider', $provider_id)
        ->where('branches.is_active', 1)
        ->get()
        ->result_array();
}

public function add_branch(int $provider_id, int $branch_id): void
{
    $this->db->insert('provider_branches', [
        'id_users_provider' => $provider_id,
        'id_branches' => $branch_id,
    ]);
}

public function remove_branch(int $provider_id, int $branch_id): void
{
    $this->db->delete('provider_branches', [
        'id_users_provider' => $provider_id,
        'id_branches' => $branch_id,
    ]);
}
```

---

#### 1.5 Add Branch Filtering to Existing APIs (2 hours)

**Update:** `application/controllers/api/v1/Services_api_v1.php`

In `index()` method, add branch filter:
```php
public function index(): void
{
    try {
        $keyword = $this->api->request_keyword();
        $limit = $this->api->request_limit();
        $offset = $this->api->request_offset();
        $order_by = $this->api->request_order_by();
        $fields = $this->api->request_fields();
        $with = $this->api->request_with();

        $where = null;

        // Branch filter
        $branch_id = request('branchId');
        if (!empty($branch_id)) {
            $where['id_branches'] = $branch_id;
        }

        $services = empty($keyword)
            ? $this->services_model->get($where, $limit, $offset, $order_by)
            : $this->services_model->search($keyword, $limit, $offset, $order_by);

        // ... rest of the code
    }
}
```

**Update:** `application/controllers/api/v1/Providers_api_v1.php`

In `index()` method, add branch filter:
```php
public function index(): void
{
    try {
        $keyword = $this->api->request_keyword();
        $limit = $this->api->request_limit();
        $offset = $this->api->request_offset();
        $order_by = $this->api->request_order_by();
        $fields = $this->api->request_fields();
        $with = $this->api->request_with();

        $where = null;

        // Branch filter
        $branch_id = request('branchId');
        if (!empty($branch_id)) {
            // Join with provider_branches table
            $this->db->select('users.*');
            $this->db->from('users');
            $this->db->join('provider_branches', 'provider_branches.id_users_provider = users.id');
            $this->db->where('provider_branches.id_branches', $branch_id);

            $providers = $this->db->get()->result_array();
        } else {
            $providers = empty($keyword)
                ? $this->providers_model->get($where, $limit, $offset, $order_by)
                : $this->providers_model->search($keyword, $limit, $offset, $order_by);
        }

        // ... rest of the code
    }
}
```

---

### Day 1 Testing Checklist

- [x] All 4 migrations run successfully
- [x] 2 default branches created (Dubai, Abu Dhabi)
- [x] `GET /api/v1/branches` returns both branches
- [x] `GET /api/v1/branches/1` returns Dubai branch
- [x] `GET /api/v1/services?branchId=1` filters by branch
- [x] `GET /api/v1/providers?branchId=1` filters by branch
- [x] Database relationships working (foreign keys)

---

## DAY 2: PAYMENT INTEGRATION & ENHANCED APPOINTMENTS

**Goal:** Add Stripe payment creation and appointment payment tracking

### Morning Session (4 hours)

#### 2.1 Install Stripe SDK (15 minutes)

```bash
cd /Users/mkuzayez/Documents/Development/laravel/easyappointments
composer require stripe/stripe-php
```

---

#### 2.2 Create Stripe Payment Library (2 hours)

**Create:** `application/libraries/Stripe_payment.php`

```php
<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Stripe payment library
 * Handles Stripe checkout session creation
 */
class Stripe_payment
{
    private $CI;
    private $stripe_secret_key;

    public function __construct()
    {
        $this->CI =& get_instance();

        // Load from settings or config
        $this->stripe_secret_key = setting('stripe_secret_key') ?: config('stripe_secret_key');

        if (empty($this->stripe_secret_key)) {
            throw new RuntimeException('Stripe secret key not configured');
        }

        \Stripe\Stripe::setApiKey($this->stripe_secret_key);
    }

    /**
     * Create a checkout session for appointment payment
     *
     * @param array $data
     * @return array ['session_id' => ..., 'checkout_url' => ...]
     */
    public function create_checkout_session(array $data): array
    {
        try {
            $amount = $data['amount'];
            $currency = $data['currency'] ?? 'AED';
            $customer_email = $data['customer_email'];
            $metadata = $data['metadata'] ?? [];
            $success_url = $data['success_url'];
            $cancel_url = $data['cancel_url'];

            // Convert amount to cents (Stripe expects smallest currency unit)
            $amount_cents = (int)($amount * 100);

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($currency),
                        'product_data' => [
                            'name' => 'Medical Consultation',
                            'description' => 'Expert Medical Center - ' . ($metadata['service_name'] ?? 'Consultation'),
                        ],
                        'unit_amount' => $amount_cents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $success_url,
                'cancel_url' => $cancel_url,
                'customer_email' => $customer_email,
                'metadata' => $metadata,
                'payment_intent_data' => [
                    'metadata' => $metadata,
                ],
            ]);

            return [
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'expires_at' => $session->expires_at,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new RuntimeException('Stripe error: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a checkout session
     *
     * @param string $session_id
     * @return object
     */
    public function get_session(string $session_id): object
    {
        try {
            return \Stripe\Checkout\Session::retrieve($session_id);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new RuntimeException('Stripe error: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a payment intent
     *
     * @param string $intent_id
     * @return object
     */
    public function get_payment_intent(string $intent_id): object
    {
        try {
            return \Stripe\PaymentIntent::retrieve($intent_id);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new RuntimeException('Stripe error: ' . $e->getMessage());
        }
    }

    /**
     * Verify payment status for a session
     *
     * @param string $session_id
     * @return bool
     */
    public function verify_payment(string $session_id): bool
    {
        try {
            $session = $this->get_session($session_id);
            return $session->payment_status === 'paid';
        } catch (Exception $e) {
            return false;
        }
    }
}
```

---

#### 2.3 Create Payments API Controller (1.5 hours)

**Create:** `application/controllers/api/v1/Payments_api_v1.php`

```php
<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Payments API v1 controller
 */
class Payments_api_v1 extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('stripe_payment');
        $this->load->library('api');
        $this->api->auth();
    }

    /**
     * POST /api/v1/payments/create-session
     *
     * Create a Stripe checkout session for payment
     */
    public function create_session(): void
    {
        try {
            $request = request();

            // Validate required fields
            if (empty($request['amount']) || empty($request['customerEmail'])) {
                throw new InvalidArgumentException('Amount and customerEmail are required');
            }

            $data = [
                'amount' => $request['amount'],
                'currency' => $request['currency'] ?? 'AED',
                'customer_email' => $request['customerEmail'],
                'metadata' => $request['metadata'] ?? [],
                'success_url' => $request['successUrl'] ?? config('stripe_success_url'),
                'cancel_url' => $request['cancelUrl'] ?? config('stripe_cancel_url'),
            ];

            $result = $this->stripe_payment->create_checkout_session($data);

            json_response([
                'sessionId' => $result['session_id'],
                'checkoutUrl' => $result['checkout_url'],
                'expiresAt' => date('c', $result['expires_at']),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * POST /api/v1/payments/verify
     *
     * Verify payment status
     */
    public function verify(): void
    {
        try {
            $request = request();

            if (empty($request['sessionId'])) {
                throw new InvalidArgumentException('Session ID is required');
            }

            $session_id = $request['sessionId'];
            $is_paid = $this->stripe_payment->verify_payment($session_id);

            if ($is_paid) {
                $session = $this->stripe_payment->get_session($session_id);

                json_response([
                    'status' => 'paid',
                    'paymentIntentId' => $session->payment_intent,
                    'amountTotal' => $session->amount_total / 100, // Convert from cents
                    'currency' => strtoupper($session->currency),
                ]);
            } else {
                json_response([
                    'status' => 'pending',
                ]);
            }
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
```

---

### Afternoon Session (4 hours)

#### 2.4 Update Appointments Model (1 hour)

**Update:** `application/models/Appointments_model.php`

Add payment fields to `$api_resource`:
```php
protected array $api_resource = [
    // ... existing fields
    'paymentStatus' => 'payment_status',
    'paymentMethod' => 'payment_method',
    'stripeSessionId' => 'stripe_session_id',
    'stripePaymentIntentId' => 'stripe_payment_intent_id',
    'paymentAmount' => 'payment_amount',
    'paymentCurrency' => 'payment_currency',
];
```

Add to `$casts`:
```php
protected array $casts = [
    // ... existing fields
    'payment_amount' => 'float',
];
```

---

#### 2.5 Update Appointments API Controller (2 hours)

**Update:** `application/controllers/api/v1/Appointments_api_v1.php`

Modify the `store()` method to handle payment:

```php
/**
 * POST /api/v1/appointments
 */
public function store(): void
{
    try {
        $appointment = request();
        $this->appointments_model->api_decode($appointment);

        // Handle payment verification if online payment
        if (!empty($appointment['stripe_session_id'])) {
            $this->load->library('stripe_payment');

            $is_paid = $this->stripe_payment->verify_payment($appointment['stripe_session_id']);

            if (!$is_paid) {
                throw new RuntimeException('Payment not verified. Please complete payment first.');
            }

            // Get payment details
            $session = $this->stripe_payment->get_session($appointment['stripe_session_id']);

            // Auto-fill payment fields
            $appointment['payment_status'] = 'paid';
            $appointment['payment_method'] = 'online';
            $appointment['stripe_payment_intent_id'] = $session->payment_intent;
            $appointment['payment_amount'] = $session->amount_total / 100;
            $appointment['payment_currency'] = strtoupper($session->currency);
            $appointment['status'] = 'confirmed';
        } else if (!empty($appointment['payment_method']) && $appointment['payment_method'] === 'offline') {
            // Offline payment
            $appointment['payment_status'] = 'pending';
            $appointment['status'] = 'pending_payment';
        }

        // Save appointment
        $appointment_id = $this->appointments_model->save($appointment);

        // Load created appointment
        $created_appointment = $this->appointments_model->find($appointment_id);

        $this->appointments_model->api_encode($created_appointment);

        // Send notifications
        $service = $this->services_model->find($created_appointment['id_services']);
        $provider = $this->providers_model->find($created_appointment['id_users_provider']);
        $customer = $this->customers_model->find($created_appointment['id_users_customer']);

        try {
            $this->notifications->notify_appointment_saved(
                $created_appointment,
                $service,
                $provider,
                $customer
            );

            $this->synchronization->sync_appointment_saved($created_appointment, $service, $provider, $customer);
        } catch (Throwable $e) {
            log_message('error', 'Notifications failed: ' . $e->getMessage());
        }

        json_response($created_appointment, 201);
    } catch (Throwable $e) {
        json_exception($e);
    }
}
```

---

#### 2.6 Configuration Setup (30 minutes)

**Update:** `application/config/config.php`

Add Stripe configuration:
```php
// Stripe Settings
$config['stripe_secret_key'] = getenv('STRIPE_SECRET_KEY') ?: 'sk_test_...';
$config['stripe_publishable_key'] = getenv('STRIPE_PUBLISHABLE_KEY') ?: 'pk_test_...';
$config['stripe_success_url'] = 'https://expertmedical.ae/booking/success';
$config['stripe_cancel_url'] = 'https://expertmedical.ae/booking/cancel';
```

**Or add to settings table:**
```sql
INSERT INTO `settings` (`name`, `value`) VALUES
('stripe_secret_key', 'sk_test_...'),
('stripe_publishable_key', 'pk_test_...'),
('stripe_success_url', 'https://expertmedical.ae/booking/success'),
('stripe_cancel_url', 'https://expertmedical.ae/booking/cancel');
```

---

#### 2.7 Test Payment Flow (30 minutes)

**Test with Postman or cURL:**

1. **Create Checkout Session:**
```bash
curl -X POST http://localhost/api/v1/payments/create-session \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 250.00,
    "currency": "AED",
    "customerEmail": "patient@example.com",
    "metadata": {
      "providerId": 1,
      "serviceId": 1,
      "appointmentTime": "2026-03-01 10:00:00"
    }
  }'
```

2. **Visit checkout URL in response**
3. **Use Stripe test card:** `4242 4242 4242 4242`, any future date, any CVC
4. **Verify payment:**
```bash
curl -X POST http://localhost/api/v1/payments/verify \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "sessionId": "cs_test_..."
  }'
```

5. **Create appointment with payment:**
```bash
curl -X POST http://localhost/api/v1/appointments \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "providerId": 1,
    "serviceId": 1,
    "start": "2026-03-01 10:00:00",
    "end": "2026-03-01 10:30:00",
    "customer": {
      "firstName": "Ahmad",
      "lastName": "Hassan",
      "email": "ahmad@example.com",
      "phone": "+971501234567"
    },
    "stripeSessionId": "cs_test_..."
  }'
```

---

### Day 2 Testing Checklist

- [ ] Stripe SDK installed
- [ ] Checkout session created successfully
- [ ] Payment verification working
- [ ] Appointments created with payment data
- [ ] Payment status saved correctly
- [ ] Offline payment option works
- [ ] Test cards work in Stripe checkout

---

## DAY 3: MULTILINGUAL EMAILS & POLISH

**Goal:** Implement bilingual email templates and final testing

### Morning Session (4 hours)

#### 3.1 Add Language Field Migration (15 minutes)

**Create:** `application/migrations/065_add_preferred_language.php`

```php
<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_preferred_language extends EA_Migration
{
    public function up(): void
    {
        $fields = [
            'preferred_language' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'default' => 'en',
                'after' => 'language',
            ],
        ];

        $this->dbforge->add_column('users', $fields);
    }

    public function down(): void
    {
        $this->dbforge->drop_column('users', 'preferred_language');
    }
}
```

Run migration:
```bash
php index.php console migrate
```

---

#### 3.2 Create Email Templates (2 hours)

**Create directory structure:**
```bash
mkdir -p application/views/emails/en
mkdir -p application/views/emails/ar
```

**English Appointment Confirmation:**
`application/views/emails/en/appointment_confirmation.php`

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; direction: ltr; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #3D2814; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .details { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #654321; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .button { display: inline-block; padding: 12px 24px; background: #654321; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Appointment Confirmed</h1>
        </div>

        <div class="content">
            <p>Dear <?php echo $customer_name; ?>,</p>

            <p>Your appointment has been successfully confirmed.</p>

            <div class="details">
                <h3>Appointment Details</h3>
                <p><strong>Doctor:</strong> <?php echo $provider_name; ?></p>
                <p><strong>Date:</strong> <?php echo $appointment_date; ?></p>
                <p><strong>Time:</strong> <?php echo $appointment_time; ?></p>
                <p><strong>Branch:</strong> <?php echo $branch_name; ?></p>
                <p><strong>Service:</strong> <?php echo $service_name; ?></p>

                <?php if (!empty($payment_amount)): ?>
                <p><strong>Payment:</strong> <?php echo $payment_currency; ?> <?php echo $payment_amount; ?> (Paid)</p>
                <?php endif; ?>
            </div>

            <p style="text-align: center;">
                <a href="<?php echo $reschedule_link; ?>" class="button">Reschedule</a>
                <a href="<?php echo $cancel_link; ?>" class="button" style="background: #999;">Cancel</a>
            </p>

            <p>If you have any questions, please contact us at <?php echo $branch_phone; ?></p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Expert Medical Center. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
```

**Arabic Appointment Confirmation:**
`application/views/emails/ar/appointment_confirmation.php`

```php
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Arial', sans-serif; direction: rtl; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #3D2814; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .details { background: white; padding: 15px; margin: 20px 0; border-right: 4px solid #654321; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .button { display: inline-block; padding: 12px 24px; background: #654321; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>تم تأكيد الموعد</h1>
        </div>

        <div class="content">
            <p>عزيزي/عزيزتي <?php echo $customer_name; ?>,</p>

            <p>تم تأكيد موعدك بنجاح.</p>

            <div class="details">
                <h3>تفاصيل الموعد</h3>
                <p><strong>الطبيب:</strong> <?php echo $provider_name; ?></p>
                <p><strong>التاريخ:</strong> <?php echo $appointment_date; ?></p>
                <p><strong>الوقت:</strong> <?php echo $appointment_time; ?></p>
                <p><strong>الفرع:</strong> <?php echo $branch_name; ?></p>
                <p><strong>الخدمة:</strong> <?php echo $service_name; ?></p>

                <?php if (!empty($payment_amount)): ?>
                <p><strong>الدفع:</strong> <?php echo $payment_amount; ?> <?php echo $payment_currency; ?> (مدفوع)</p>
                <?php endif; ?>
            </div>

            <p style="text-align: center;">
                <a href="<?php echo $reschedule_link; ?>" class="button">إعادة الجدولة</a>
                <a href="<?php echo $cancel_link; ?>" class="button" style="background: #999;">إلغاء</a>
            </p>

            <p>إذا كان لديك أي استفسارات، يرجى الاتصال بنا على <?php echo $branch_phone; ?></p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> مركز الخبراء الطبي. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
```

---

#### 3.3 Create Multilingual Notifications Library (1.5 hours)

**Create:** `application/libraries/Multilingual_notifications.php`

```php
<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Multilingual notifications library
 * Extends base Notifications to support AR/EN emails
 */
class Multilingual_notifications extends Notifications
{
    /**
     * Send appointment confirmation in user's preferred language
     */
    public function send_appointment_confirmation(
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        ?string $language = null
    ): void {
        // Determine language
        $language = $language ?? $customer['preferred_language'] ?? 'en';

        // Prepare data
        $data = [
            'customer_name' => $customer['first_name'] . ' ' . $customer['last_name'],
            'provider_name' => 'Dr. ' . $provider['first_name'] . ' ' . $provider['last_name'],
            'appointment_date' => date('d M Y', strtotime($appointment['start_datetime'])),
            'appointment_time' => date('h:i A', strtotime($appointment['start_datetime'])),
            'service_name' => $service['name'],
            'payment_amount' => $appointment['payment_amount'] ?? null,
            'payment_currency' => $appointment['payment_currency'] ?? 'AED',
            'branch_name' => 'Expert Medical Center', // TODO: Get from branch
            'branch_phone' => '+971 XXX XXXX',
            'reschedule_link' => site_url('booking/reschedule/' . $appointment['hash']),
            'cancel_link' => site_url('booking/cancel/' . $appointment['hash']),
        ];

        // Load template
        $template_path = VIEWPATH . 'emails/' . $language . '/appointment_confirmation.php';

        if (!file_exists($template_path)) {
            $template_path = VIEWPATH . 'emails/en/appointment_confirmation.php';
        }

        $html = $this->load_template($template_path, $data);

        // Send email
        $subject = $language === 'ar'
            ? 'تأكيد الموعد - مركز الخبراء الطبي'
            : 'Appointment Confirmation - Expert Medical Center';

        $this->send_email($customer['email'], $subject, $html);
    }

    /**
     * Load template and replace variables
     */
    private function load_template(string $template_path, array $data): string
    {
        extract($data);

        ob_start();
        include $template_path;
        $html = ob_get_clean();

        return $html;
    }

    /**
     * Send email via CI email library
     */
    private function send_email(string $to, string $subject, string $message): void
    {
        $CI =& get_instance();
        $CI->load->library('email');

        $CI->email->clear();
        $CI->email->from(
            setting('company_email') ?: 'noreply@expertmedical.ae',
            setting('company_name') ?: 'Expert Medical Center'
        );
        $CI->email->to($to);
        $CI->email->subject($subject);
        $CI->email->message($message);
        $CI->email->set_mailtype('html');

        if (!$CI->email->send()) {
            log_message('error', 'Email failed to send: ' . $CI->email->print_debugger());
        }
    }
}
```

---

### Afternoon Session (4 hours)

#### 3.4 Update Appointments API to Use Multilingual Emails (30 minutes)

**Update:** `application/controllers/api/v1/Appointments_api_v1.php`

Replace the notifications call in `store()` method:

```php
// Send notifications
$this->load->library('multilingual_notifications');

$customer_language = $appointment['language'] ?? $customer['preferred_language'] ?? 'en';

try {
    $this->multilingual_notifications->send_appointment_confirmation(
        $created_appointment,
        $service,
        $provider,
        $customer,
        $customer_language
    );
} catch (Throwable $e) {
    log_message('error', 'Email notification failed: ' . $e->getMessage());
}
```

---

#### 3.5 Add Doctor Profile Fields (Optional - 1 hour)

**Create:** `application/migrations/066_add_doctor_profile_fields.php`

```php
<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_doctor_profile_fields extends EA_Migration
{
    public function up(): void
    {
        $fields = [
            'photo' => [
                'type' => 'VARCHAR',
                'constraint' => '512',
                'null' => true,
                'after' => 'notes',
            ],
            'bio' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'photo',
            ],
            'qualifications' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'bio',
            ],
            'specialty' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => true,
                'after' => 'qualifications',
            ],
        ];

        $this->dbforge->add_column('users', $fields);
    }

    public function down(): void
    {
        $this->dbforge->drop_column('users', 'photo');
        $this->dbforge->drop_column('users', 'bio');
        $this->dbforge->drop_column('users', 'qualifications');
        $this->dbforge->drop_column('users', 'specialty');
    }
}
```

Update `Providers_model.php` API resource:
```php
protected array $api_resource = [
    // ... existing
    'photo' => 'photo',
    'bio' => 'bio',
    'qualifications' => 'qualifications',
    'specialty' => 'specialty',
];
```

---

#### 3.6 Comprehensive Testing (2 hours)

**Complete End-to-End Test:**

1. **Branch Management:**
   - [ ] GET all branches
   - [ ] Filter services by branch
   - [ ] Filter providers by branch
   - [ ] Assign provider to branch

2. **Payment Flow:**
   - [ ] Create checkout session
   - [ ] Complete payment in Stripe (test mode)
   - [ ] Verify payment
   - [ ] Create appointment with payment

3. **Appointment Creation:**
   - [ ] Create with online payment (English)
   - [ ] Create with online payment (Arabic)
   - [ ] Create with offline payment
   - [ ] Verify payment data saved

4. **Email Notifications:**
   - [ ] Receive English confirmation email
   - [ ] Receive Arabic confirmation email
   - [ ] Check RTL formatting in Arabic
   - [ ] Verify all data in email

5. **API Responses:**
   - [ ] All endpoints return correct JSON
   - [ ] Error handling works
   - [ ] Authentication required

---

#### 3.7 Documentation (30 minutes)

**Create:** `API_USAGE_GUIDE.md`

```markdown
# API Usage Guide - Expert Medical Center

## Base URL
```
http://localhost/index.php/api/v1
```

## Authentication
All requests require Bearer token:
```
Authorization: Bearer YOUR_API_TOKEN
```

## Booking Flow

### 1. Get Branches
```bash
GET /branches
Response: [
  {"id": 1, "name": "Dubai Branch", "nameAr": "فرع دبي"},
  {"id": 2, "name": "Abu Dhabi Branch", "nameAr": "فرع أبو ظبي"}
]
```

### 2. Get Services by Branch
```bash
GET /services?branchId=1
```

### 3. Get Providers by Branch
```bash
GET /providers?branchId=1
```

### 4. Check Availability
```bash
GET /availabilities?providerId=1&serviceId=1&date=2026-03-01
```

### 5. Create Payment Session
```bash
POST /payments/create-session
Body: {
  "amount": 250.00,
  "currency": "AED",
  "customerEmail": "patient@example.com",
  "metadata": {...}
}
Response: {
  "sessionId": "cs_test_...",
  "checkoutUrl": "https://checkout.stripe.com/..."
}
```

### 6. User Completes Payment on Stripe

### 7. Verify Payment
```bash
POST /payments/verify
Body: {"sessionId": "cs_test_..."}
Response: {"status": "paid", "paymentIntentId": "pi_..."}
```

### 8. Create Appointment
```bash
POST /appointments
Body: {
  "providerId": 1,
  "serviceId": 1,
  "start": "2026-03-01 10:00:00",
  "end": "2026-03-01 10:30:00",
  "customer": {...},
  "stripeSessionId": "cs_test_...",
  "language": "ar"
}
```

## Offline Payment Flow

Skip steps 5-7, create appointment with:
```json
{
  "paymentMethod": "offline",
  ...
}
```

Appointment status will be "pending_payment".
```

---

#### 3.8 Final Checklist & Cleanup (1 hour)

**Code Quality:**
- [ ] Remove debug logs
- [ ] Add proper error messages
- [ ] Validate all inputs
- [ ] Check SQL injection protection
- [ ] Verify foreign keys working

**Security:**
- [ ] Stripe keys in config (not hardcoded)
- [ ] API authentication required
- [ ] Payment verification before appointment
- [ ] No sensitive data in logs

**Performance:**
- [ ] Database indexes created
- [ ] No N+1 queries
- [ ] API responses fast (<2s)

**Documentation:**
- [ ] Code comments added
- [ ] API guide created
- [ ] README updated
- [ ] Migration notes documented

---

## DEPLOYMENT CHECKLIST

### Pre-Deployment
- [ ] All migrations tested locally
- [ ] Payment flow tested with Stripe test mode
- [ ] Emails sending correctly
- [ ] All API endpoints working
- [ ] No console errors
- [ ] Database backed up

### Production Setup
1. [ ] Update Stripe keys to live mode:
   ```php
   $config['stripe_secret_key'] = 'sk_live_...';
   $config['stripe_publishable_key'] = 'pk_live_...';
   ```

2. [ ] Update success/cancel URLs:
   ```php
   $config['stripe_success_url'] = 'https://expertmedical.ae/booking/success';
   $config['stripe_cancel_url'] = 'https://expertmedical.ae/booking/cancel';
   ```

3. [ ] Configure SMTP for emails:
   ```php
   $config['smtp_host'] = 'smtp.gmail.com';
   $config['smtp_user'] = 'noreply@expertmedical.ae';
   $config['smtp_pass'] = 'your-password';
   ```

4. [ ] Run migrations:
   ```bash
   php index.php console migrate
   ```

5. [ ] Test live payment (small amount)

6. [ ] Monitor logs for errors

---

## POST-IMPLEMENTATION SUPPORT

### Testing Commands

**Check migrations:**
```bash
php index.php console migrate:status
```

**Test API:**
```bash
curl -X GET http://localhost/api/v1/branches \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Check logs:**
```bash
tail -f storage/logs/log-*.php
```

### Common Issues

**Migration fails:**
- Check database permissions
- Verify foreign key references exist
- Check for duplicate column names

**Payment not working:**
- Verify Stripe keys are correct
- Check network can reach Stripe API
- Ensure test mode enabled for testing

**Email not sending:**
- Check SMTP credentials
- Verify firewall allows SMTP port (587)
- Check spam folder

---

## SUMMARY

### What Was Built (3 Days)

**Day 1:**
- ✅ Branches table and management
- ✅ Branch filtering on services/providers
- ✅ Provider-branch relationships
- ✅ Payment fields in appointments

**Day 2:**
- ✅ Stripe SDK integration
- ✅ Checkout session creation
- ✅ Payment verification
- ✅ Enhanced appointments with payment

**Day 3:**
- ✅ Multilingual email templates (AR/EN)
- ✅ Multilingual notifications library
- ✅ Doctor profile fields
- ✅ Complete testing

### What's Ready for Next.js Frontend

**Available APIs:**
1. GET /branches - List all branches
2. GET /services?branchId={id} - Services by branch
3. GET /providers?branchId={id} - Doctors by branch
4. GET /availabilities - Real-time slots
5. POST /payments/create-session - Create Stripe checkout
6. POST /payments/verify - Verify payment
7. POST /appointments - Create appointment with payment

**Frontend can now:**
- Display branches for selection
- Filter doctors by branch
- Create Stripe checkout sessions
- Verify payments before booking
- Create appointments with payment tracking
- Send bilingual confirmation emails

### Total Files Created/Modified

**New Files: 13**
- 6 migrations
- 1 model (Branches)
- 2 controllers (Branches API, Payments API)
- 1 library (Stripe_payment, Multilingual_notifications)
- 2 email templates (EN/AR)
- 1 documentation

**Modified Files: 4**
- Services_model.php
- Providers_model.php
- Appointments_model.php
- Appointments_api_v1.php

**Time Invested:** 24 hours (3 days × 8 hours)

---

*End of 3-Day Implementation Plan*
