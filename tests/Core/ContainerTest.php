<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Core;

use PHPUnit\Framework\TestCase;
use YamlNs\WppFramework\Core\Container;

// ---------------------------------------------------------------------------
// Fixture classes kept here so these tests stay self-contained.
// ---------------------------------------------------------------------------

class SimpleService {}

interface PaymentGateway {}

class StripeGateway implements PaymentGateway {}

class PaypalGateway implements PaymentGateway {}

class CheckoutService
{
    public function __construct(public PaymentGateway $gateway) {}
}

class DependentService
{
    public function __construct(public SimpleService $simple) {}
}

class ServiceWithDefault
{
    public function __construct(public string $name = 'default') {}
}

class CircularA
{
    public function __construct(CircularB $b) {}
}

class CircularB
{
    public function __construct(CircularA $a) {}
}

class ServiceWithUnion
{
    public function __construct(SimpleService|DependentService $dep) {}
}

class ServiceWithRequiredScalar
{
    public function __construct(public string $name) {}
}

// ---------------------------------------------------------------------------

final class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    // --- autowiring ---------------------------------------------------------

    public function test_autowires_simple_class(): void
    {
        $instance = $this->container->get(SimpleService::class);

        $this->assertInstanceOf(SimpleService::class, $instance);
    }

    public function test_autowires_constructor_dependencies(): void
    {
        $instance = $this->container->get(DependentService::class);

        $this->assertInstanceOf(DependentService::class, $instance);
        $this->assertInstanceOf(SimpleService::class, $instance->simple);
    }

    public function test_autowiring_resolves_optional_param_with_default(): void
    {
        $instance = $this->container->get(ServiceWithDefault::class);

        $this->assertSame('default', $instance->name);
    }

    // --- bind (transient) ---------------------------------------------------

    public function test_bind_returns_new_instance_each_call(): void
    {
        $this->container->bind(SimpleService::class);

        $a = $this->container->get(SimpleService::class);
        $b = $this->container->get(SimpleService::class);

        $this->assertNotSame($a, $b);
    }

    public function test_bind_with_callable(): void
    {
        $this->container->bind(SimpleService::class, fn () => new SimpleService());

        $a = $this->container->get(SimpleService::class);
        $b = $this->container->get(SimpleService::class);

        $this->assertNotSame($a, $b);
    }

    // --- singleton ----------------------------------------------------------

    public function test_singleton_returns_same_instance(): void
    {
        $this->container->singleton(SimpleService::class);

        $a = $this->container->get(SimpleService::class);
        $b = $this->container->get(SimpleService::class);

        $this->assertSame($a, $b);
    }

    public function test_singleton_with_callable(): void
    {
        $this->container->singleton(SimpleService::class, fn () => new SimpleService());

        $a = $this->container->get(SimpleService::class);
        $b = $this->container->get(SimpleService::class);

        $this->assertSame($a, $b);
    }

    public function test_factory_must_return_object(): void
    {
        $this->container->bind(SimpleService::class, fn () => null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Container factory must return an object.');

        $this->container->get(SimpleService::class);
    }

    // --- instance -----------------------------------------------------------

    public function test_instance_returns_registered_object(): void
    {
        $service = new SimpleService();
        $this->container->instance(SimpleService::class, $service);

        $this->assertSame($service, $this->container->get(SimpleService::class));
    }

    // --- alias --------------------------------------------------------------

    public function test_alias_resolves_to_abstract(): void
    {
        $this->container->singleton(SimpleService::class);
        $this->container->alias(SimpleService::class, 'simple');

        $this->assertSame(
            $this->container->get(SimpleService::class),
            $this->container->get('simple')
        );
    }

    public function test_tagged_resolves_grouped_services(): void
    {
        $this->container->bind(StripeGateway::class);
        $this->container->bind(PaypalGateway::class);
        $this->container->tag([StripeGateway::class, PaypalGateway::class], 'gateways');

        $services = $this->container->tagged('gateways');

        $this->assertInstanceOf(StripeGateway::class, $services[0]);
        $this->assertInstanceOf(PaypalGateway::class, $services[1]);
    }

    public function test_contextual_binding_resolves_dependency_for_specific_class(): void
    {
        $this->container->when(CheckoutService::class)
            ->needs(PaymentGateway::class)
            ->give(StripeGateway::class);

        $service = $this->container->get(CheckoutService::class);

        $this->assertInstanceOf(StripeGateway::class, $service->gateway);
    }

    // --- has / forget -------------------------------------------------------

    public function test_has_returns_true_for_known_class(): void
    {
        $this->assertTrue($this->container->has(SimpleService::class));
    }

    public function test_has_returns_false_for_unknown(): void
    {
        $this->assertFalse($this->container->has('NonExistentClass'));
    }

    public function test_forget_removes_singleton_cache(): void
    {
        $this->container->singleton(SimpleService::class);
        $a = $this->container->get(SimpleService::class);

        $this->container->forget(SimpleService::class);
        $b = $this->container->get(SimpleService::class);

        $this->assertNotSame($a, $b);
    }

    public function test_forget_binding_removes_definition(): void
    {
        $this->container->bind('service', fn () => new SimpleService());

        $this->assertTrue($this->container->has('service'));

        $this->container->forgetBinding('service');

        $this->assertFalse($this->container->has('service'));
    }

    // --- circular dependency ------------------------------------------------

    public function test_circular_dependency_throws_with_chain(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Circular dependency detected/');

        $this->container->get(CircularA::class);
    }

    // --- union types --------------------------------------------------------

    public function test_union_type_throws_explicit_error(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Union types are not supported/');

        $this->container->get(ServiceWithUnion::class);
    }

    // --- call ---------------------------------------------------------------

    public function test_call_injects_dependencies(): void
    {
        $result = $this->container->call(function (SimpleService $s): string {
            return $s::class;
        });

        $this->assertSame(SimpleService::class, $result);
    }

    public function test_call_with_explicit_parameter_by_name(): void
    {
        $service = new SimpleService();

        $result = $this->container->call(
            function (SimpleService $s): SimpleService { return $s; },
            ['s' => $service]
        );

        $this->assertSame($service, $result);
    }

    public function test_call_with_explicit_parameter_by_classname(): void
    {
        $service = new SimpleService();

        $result = $this->container->call(
            function (SimpleService $s): SimpleService { return $s; },
            [SimpleService::class => $service]
        );

        $this->assertSame($service, $result);
    }

    public function test_call_with_union_type_throws_explicit_error(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Union types are not supported/');

        $this->container->call(function (SimpleService|DependentService $service): void {});
    }

    public function test_call_without_type_or_default_throws_explicit_error(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Cannot resolve parameter 'value'");

        $this->container->call(function ($value): void {});
    }

    public function test_required_scalar_constructor_dependency_throws_explicit_error(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Cannot resolve dependency 'name'");

        $this->container->get(ServiceWithRequiredScalar::class);
    }
}
