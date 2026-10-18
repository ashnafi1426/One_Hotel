<?php

namespace Tests\Unit\Events;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

/**
 * Bug Condition Exploration Test for OrderStatusUpdated Event
 * 
 * This test MUST FAIL on unfixed code to prove the bug exists.
 * 
 * Bug: OrderStatusUpdated event references $this->order in broadcastWith() method
 * but does not declare the order property or assign it in the constructor.
 * 
 * Expected failure: "Undefined property: App\Events\OrderStatusUpdated::$order"
 */
class OrderStatusUpdatedTest extends TestCase
{
    use WithoutMiddleware;

    /**
     * Create a test order without database interaction
     */
    private function createTestOrder(): Order
    {
        // Create an Order instance without saving to database
        $order = new Order();
        $order->id = 'test-order-id-123';
        $order->order_number = 'ORD-20240101-0001';
        $order->hotel_id = '41b094f8-7ed2-4af0-bd16-6f0533e34544';
        $order->status = 'pending';
        $order->chef_id = null;
        $order->created_at = now();
        
        // Mock the relationships to avoid database queries
        $order->setRelation('guest', null);
        $order->setRelation('room', null);
        $order->setRelation('table', null);
        $order->setRelation('orderItems', collect([]));
        
        return $order;
    }

    /**
     * @test
     * Property 1: Bug Condition - Order Property Access Fatal Error
     * 
     * This test encodes the expected behavior: when OrderStatusUpdated event is 
     * instantiated and broadcastWith() is called, it should successfully access 
     * $this->order->guest and $this->order->created_at.
     * 
     * On UNFIXED code: This test will FAIL with "Undefined property: App\Events\OrderStatusUpdated::$order"
     * On FIXED code: This test will PASS, confirming the bug is resolved.
     * 
     * **Validates: Bug Condition - Missing order property declaration and assignment**
     */
    public function test_event_broadcastWith_includes_order_property_data()
    {
        // Arrange: Create a test order
        $order = $this->createTestOrder();

        // Act: Instantiate the OrderStatusUpdated event
        $event = new OrderStatusUpdated($order);

        // This is where the bug manifests - broadcastWith() method tries to access:
        // - $this->order->guest (line 72 in OrderStatusUpdated.php)
        // - $this->order->created_at (line 81 in OrderStatusUpdated.php)
        // 
        // But $this->order property doesn't exist in the unfixed code!
        // 
        // Expected Error (unfixed code): "Undefined property: App\Events\OrderStatusUpdated::$order"
        // Expected Behavior (fixed code): Should return broadcast data array with 'order' key
        
        try {
            $broadcastData = $event->broadcastWith();
            
            // Assert: If we get here (no error), verify broadcast data includes 'order' information
            // This proves that $this->order is accessible in broadcastWith()
            $this->assertIsArray($broadcastData, 'broadcastWith() should return an array');
            
            $this->assertArrayHasKey('order', $broadcastData, 
                'Broadcast data must include order information');
            
            // Verify the order sub-array contains expected fields that depend on $this->order
            $this->assertArrayHasKey('created_at', $broadcastData['order'], 
                'Order data must include created_at timestamp from $this->order->created_at');
            
            $this->assertArrayHasKey('id', $broadcastData['order'],
                'Order data must include id');
            
            $this->assertArrayHasKey('order_number', $broadcastData['order'],
                'Order data must include order_number');
            
            $this->assertArrayHasKey('status', $broadcastData['order'],
                'Order data must include status');

            // Verify guest_name key exists (even if null) - this proves $this->order->guest was accessible
            $this->assertArrayHasKey('guest_name', $broadcastData['order'], 
                'Order data must include guest_name field (accessing $this->order->guest)');
                
        } catch (\ErrorException $e) {
            // On unfixed code, Laravel converts the PHP error to ErrorException
            $this->assertStringContainsString('Undefined property', $e->getMessage(),
                'Expected error message about undefined property');
            $this->assertStringContainsString('::$order', $e->getMessage(),
                'Error should specifically mention missing $order property');
            
            // Document the error for the task
            $errorMessage = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            // Fail the test with documentation of the bug
            $this->fail(
                "BUG CONFIRMED: OrderStatusUpdated event is missing \$order property.\n\n" .
                "Error: {$errorMessage}\n" .
                "File: {$errorFile}\n" .
                "Line: {$errorLine}\n\n" .
                "Root Cause: The OrderStatusUpdated event class does not declare or assign \$this->order property,\n" .
                "but the broadcastWith() method attempts to access \$this->order->guest and \$this->order->created_at.\n\n" .
                "Expected Fix: Add 'public Order \$order;' property declaration and assign it in the constructor."
            );
        } catch (\Error $e) {
            // Also catch PHP Error in case error handling is different
            $this->assertStringContainsString('Undefined property', $e->getMessage(),
                'Expected error message about undefined property');
            $this->assertStringContainsString('::$order', $e->getMessage(),
                'Error should specifically mention missing $order property');
            
            // Document the error for the task
            $errorMessage = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            // Fail the test with documentation of the bug
            $this->fail(
                "BUG CONFIRMED: OrderStatusUpdated event is missing \$order property.\n\n" .
                "Error: {$errorMessage}\n" .
                "File: {$errorFile}\n" .
                "Line: {$errorLine}\n\n" .
                "Root Cause: The OrderStatusUpdated event class does not declare or assign \$this->order property,\n" .
                "but the broadcastWith() method attempts to access \$this->order->guest and \$this->order->created_at.\n\n" .
                "Expected Fix: Add 'public Order \$order;' property declaration and assign it in the constructor."
            );
        }
    }
}
