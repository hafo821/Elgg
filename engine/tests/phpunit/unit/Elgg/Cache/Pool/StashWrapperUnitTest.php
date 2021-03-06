<?php

namespace Elgg\Cache\Pool;

/**
 * @group UnitTests
 */
class StashWrapperUnitTest extends \Elgg\UnitTestCase implements \Elgg\Cache\Pool\TestCase {

	public function up() {

	}

	public function down() {

	}

	public function testGetDoesNotRegenerateValueFromCallbackOnHit() {
		$pool = StashWrapper::createEphemeral();

		$pool->get('foo', function () {
			return 1;
		});
		$result = $pool->get('foo', function () {
			return 2;
		});
		$this->assertEquals(1, $result);
	}

	public function testGetRegeneratesValueFromCallbackOnMiss() {
		$pool = StashWrapper::createEphemeral();

		$result = $pool->get('foo', function () {
			return 1;
		});
		$this->assertEquals(1, $result);
	}

	public function testInvalidateForcesTheSpecifiedValueToBeRegenerated() {
		$pool = StashWrapper::createEphemeral();

		$result = $pool->get('foo', function () {
			return 1;
		});
		$this->assertEquals(1, $result);
		$pool->invalidate('foo');

		$result = $pool->get('foo', function () {
			return 2;
		});
		$this->assertEquals(2, $result);
	}

	public function testAcceptsStringAndIntKeys() {
		$pool = StashWrapper::createEphemeral();

		foreach (['123', 123] as $key) {
			$pool->put($key, 'foo');
			$pool->get($key, function () {
				return 'foo';
			});
			$pool->invalidate($key);
		}
	}

	/**
	 * @dataProvider invalidKeyProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function testPutComplainsAboutInvalidKeys($key) {
		$pool = StashWrapper::createEphemeral();
		$pool->put($key, 'foo');
	}

	/**
	 * @dataProvider invalidKeyProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function testGetComplainsAboutInvalidKeys($key) {
		$pool = StashWrapper::createEphemeral();
		$pool->get($key, function () {
			return 'foo';
		});
	}

	/**
	 * @dataProvider invalidKeyProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function testInvalidateComplainsAboutInvalidKeys($key) {
		$pool = StashWrapper::createEphemeral();
		$pool->invalidate($key);
	}

	public function invalidKeyProvider() {
		return [
			[123.1],
			[true],
			[[]],
			[new \stdClass()],
		];
	}

	/**
	 * Stash recommends always calling $item->lock() on miss to make sure that
	 * the caching is as performant as possible by avoiding multiple
	 * simultaneous regenerations of the same value.
	 *
	 * http://www.stashphp.com/Invalidation.html#stampede-protection
	 *
	 * 1. Create a new cache
	 * 2. Get any entry
	 * 3. Check that Stash\Item::lock() was called
	 * 4. Get the same entry
	 * 5. Check that Stash\Item::lock() was *not* called
	 */
	public function testEnablesStashStampedeProtection() {
		$pool = StashWrapper::createEphemeral();
		$this->markTestIncomplete();
	}
}
