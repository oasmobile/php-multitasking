#!/usr/bin/env bash
# =============================================================================
# Manual Test Script — Task 13: Release Stabilize
# Spec: .kiro/specs/release-2.0.0
# Compatible with bash 3.2 (macOS default)
# =============================================================================
set -euo pipefail

PASS=0
FAIL=0
SUMMARY=""

report() {
    local status="$1" task="$2" detail="$3"
    if [[ "$status" == "PASS" ]]; then
        PASS=$((PASS + 1))
        echo "✅ PASS  $task — $detail"
        SUMMARY="${SUMMARY}✅ PASS  $task — $detail
"
    else
        FAIL=$((FAIL + 1))
        echo "❌ FAIL  $task — $detail"
        SUMMARY="${SUMMARY}❌ FAIL  $task — $detail
"
    fi
}

# ─────────────────────────────────────────────────────────────────────────────
# 13.2 — composer install 在干净环境下无冲突
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "═══ 13.2 composer install ═══"

rm -rf vendor composer.lock
COMPOSER_OUTPUT=$(composer install --no-interaction 2>&1) || true

if echo "$COMPOSER_OUTPUT" | grep -qi "conflict"; then
    report "FAIL" "13.2" "composer install reported conflicts"
    echo "$COMPOSER_OUTPUT"
elif echo "$COMPOSER_OUTPUT" | grep -qi "Your requirements could not be resolved"; then
    report "FAIL" "13.2" "composer install could not resolve requirements"
    echo "$COMPOSER_OUTPUT"
elif echo "$COMPOSER_OUTPUT" | grep -qi "error"; then
    report "FAIL" "13.2" "composer install reported errors"
    echo "$COMPOSER_OUTPUT"
else
    report "PASS" "13.2" "composer install succeeded, dependencies resolved"
fi

# ─────────────────────────────────────────────────────────────────────────────
# 13.3 — vendor/bin/phpunit 全量测试通过，输出干净
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "═══ 13.3 phpunit full test suite ═══"

PHPUNIT_OUTPUT=$(vendor/bin/phpunit 2>&1) || true
PHPUNIT_EXIT=$?

if [[ $PHPUNIT_EXIT -ne 0 ]]; then
    report "FAIL" "13.3" "phpunit exited with code $PHPUNIT_EXIT"
    echo "$PHPUNIT_OUTPUT"
else
    HAS_ISSUES=false

    # Check for PHP deprecation warnings (from our code or dependencies)
    if echo "$PHPUNIT_OUTPUT" | grep -qi "PHP Deprecated:"; then
        echo "  ⚠ PHP Deprecated warnings found:"
        echo "$PHPUNIT_OUTPUT" | grep -i "PHP Deprecated:"
        HAS_ISSUES=true
    fi

    # Check for PHP warnings/notices/errors
    if echo "$PHPUNIT_OUTPUT" | grep -qiE "^(PHP )?(Warning|Notice|Fatal):"; then
        echo "  ⚠ PHP warnings/errors found:"
        echo "$PHPUNIT_OUTPUT" | grep -iE "^(PHP )?(Warning|Notice|Fatal):"
        HAS_ISSUES=true
    fi

    # Check for test errors or failures
    if echo "$PHPUNIT_OUTPUT" | grep -qE "Errors: [1-9]"; then
        echo "  ⚠ Test errors found"
        HAS_ISSUES=true
    fi
    if echo "$PHPUNIT_OUTPUT" | grep -qE "Failures: [1-9]"; then
        echo "  ⚠ Test failures found"
        HAS_ISSUES=true
    fi

    # Check for PHPUnit deprecations (from our code, not vendor)
    if echo "$PHPUNIT_OUTPUT" | grep -qE "PHPUnit Deprecations: [1-9]"; then
        echo "  ⚠ PHPUnit deprecations found"
        HAS_ISSUES=true
    fi

    # Check for risky tests
    if echo "$PHPUNIT_OUTPUT" | grep -qE "Risky: [1-9]"; then
        echo "  ⚠ Risky tests found"
        HAS_ISSUES=true
    fi

    if $HAS_ISSUES; then
        report "FAIL" "13.3" "Tests passed but output not clean"
        echo "$PHPUNIT_OUTPUT" | tail -5
    else
        report "PASS" "13.3" "All tests passed, output clean"
        echo "$PHPUNIT_OUTPUT" | tail -5
    fi
fi

# ─────────────────────────────────────────────────────────────────────────────
# 13.4 — PBT 测试多次运行稳定性
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "═══ 13.4 PBT stability (3 runs) ═══"

PBT_STABLE=true
for i in 1 2 3; do
    echo "  Run $i/3..."
    PBT_OUTPUT=$(vendor/bin/phpunit --filter "PbtTest" 2>&1) || true
    PBT_EXIT=$?
    if [[ $PBT_EXIT -ne 0 ]]; then
        PBT_STABLE=false
        echo "  Run $i FAILED:"
        echo "$PBT_OUTPUT"
        break
    fi
    echo "  Run $i OK"
done

if $PBT_STABLE; then
    report "PASS" "13.4" "PBT tests stable across 3 runs"
else
    report "FAIL" "13.4" "PBT tests unstable"
fi

# ─────────────────────────────────────────────────────────────────────────────
# 13.5 — 源代码公共方法签名与 docs/state/api.md 一致
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "═══ 13.5 API signature consistency ═══"

API_ISSUES=""

check_method_in_api() {
    local class_name="$1"
    local method="$2"
    if ! grep -q "$method" docs/state/api.md; then
        API_ISSUES="${API_ISSUES}  - ${class_name}::${method} — not found in api.md
"
    fi
}

check_class() {
    local class_name="$1"
    local source_file="$2"
    echo "  Checking $class_name..."

    local src_methods
    src_methods=$(grep -E '^\s+(public)\s+function\s+' "$source_file" \
        | sed 's/.*public function //' \
        | sed 's/(.*//' \
        | sort)

    while IFS= read -r method; do
        [[ -z "$method" ]] && continue
        [[ "$method" == "__construct" || "$method" == "__destruct" ]] && continue
        check_method_in_api "$class_name" "$method"
    done <<< "$src_methods"
}

check_class "Semaphore" "src/Multitasking/Semaphore.php"
check_class "MessageQueue" "src/Multitasking/MessageQueue.php"
check_class "SharedMemory" "src/Multitasking/SharedMemory.php"
check_class "WorkerInfo" "src/Multitasking/WorkerInfo.php"
check_class "WorkerManagerCompletedEvent" "src/Multitasking/WorkerManagerCompletedEvent.php"
check_class "BackgroundWorkerManager" "src/Multitasking/BackgroundWorkerManager.php"

# Check specific signature patterns in api.md
echo "  Checking detailed signatures..."

# Semaphore signatures
if ! grep -q 'acquire.*bool.*nowait.*bool' docs/state/api.md; then
    API_ISSUES="${API_ISSUES}  - Semaphore::acquire signature mismatch
"
fi
if ! grep -q 'withLock.*callable.*mixed' docs/state/api.md; then
    API_ISSUES="${API_ISSUES}  - Semaphore::withLock signature mismatch
"
fi

# MessageQueue signatures
if ! grep -q 'send.*mixed.*msg.*int.*type.*bool.*blocking.*bool' docs/state/api.md; then
    API_ISSUES="${API_ISSUES}  - MessageQueue::send signature mismatch
"
fi

# BackgroundWorkerManager signatures
if ! grep -q 'addWorker.*callable.*worker.*int.*count.*array' docs/state/api.md; then
    API_ISSUES="${API_ISSUES}  - BackgroundWorkerManager::addWorker signature mismatch
"
fi

# Check readonly properties
echo "  Checking readonly properties..."
for prop in 'id.*string.*✓' 'key.*int.*✓' 'maxAcquire.*int.*✓'; do
    if ! grep -qE "$prop" docs/state/api.md; then
        API_ISSUES="${API_ISSUES}  - Semaphore readonly property not documented: $prop
"
    fi
done

if ! grep -qE 'parentProcessId.*int.*✓' docs/state/api.md; then
    API_ISSUES="${API_ISSUES}  - BackgroundWorkerManager::parentProcessId should be readonly in api.md
"
fi

if [[ -z "$API_ISSUES" ]]; then
    report "PASS" "13.5" "All public method signatures match api.md"
else
    report "FAIL" "13.5" "Signature mismatches found"
    echo "$API_ISSUES"
fi

# ─────────────────────────────────────────────────────────────────────────────
# 13.6 — architecture.md 技术选型与 composer.json 一致
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "═══ 13.6 architecture.md vs composer.json ═══"

ARCH_ISSUES=""

# Check PHP version
COMPOSER_PHP=$(grep '"php"' composer.json | sed 's/.*: *"//' | sed 's/".*//')
echo "  PHP version in composer.json: $COMPOSER_PHP"
if ! grep -q "PHP.*8.2" docs/state/architecture.md; then
    ARCH_ISSUES="${ARCH_ISSUES}  - PHP version $COMPOSER_PHP not reflected in architecture.md
"
fi

# Check PHPUnit version
COMPOSER_PHPUNIT=$(grep '"phpunit/phpunit"' composer.json | sed 's/.*: *"//' | sed 's/".*//')
echo "  PHPUnit in composer.json: $COMPOSER_PHPUNIT"
if ! grep -q "PHPUnit.*11" docs/state/architecture.md; then
    ARCH_ISSUES="${ARCH_ISSUES}  - PHPUnit $COMPOSER_PHPUNIT not reflected in architecture.md
"
fi

# Check eris dependency
COMPOSER_ERIS=$(grep '"giorgiosironi/eris"' composer.json | sed 's/.*: *"//' | sed 's/".*//')
echo "  eris in composer.json: $COMPOSER_ERIS"
if ! grep -q "eris" docs/state/architecture.md; then
    ARCH_ISSUES="${ARCH_ISSUES}  - eris not mentioned in architecture.md
"
fi

# Check ext-pcntl
if ! grep -q "pcntl" docs/state/architecture.md; then
    ARCH_ISSUES="${ARCH_ISSUES}  - ext-pcntl not mentioned in architecture.md
"
fi

# Check oasis/logging
if ! grep -q "oasis/logging" docs/state/architecture.md; then
    ARCH_ISSUES="${ARCH_ISSUES}  - oasis/logging not mentioned in architecture.md
"
fi

# Check oasis/event
if ! grep -q "oasis/event" docs/state/architecture.md; then
    ARCH_ISSUES="${ARCH_ISSUES}  - oasis/event not mentioned in architecture.md
"
fi

if [[ -z "$ARCH_ISSUES" ]]; then
    report "PASS" "13.6" "architecture.md consistent with composer.json"
else
    report "FAIL" "13.6" "Inconsistencies found"
    echo "$ARCH_ISSUES"
fi

# ─────────────────────────────────────────────────────────────────────────────
# Summary
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "═══════════════════════════════════════════════════════════════════════"
echo "  SUMMARY: $PASS passed, $FAIL failed"
echo "═══════════════════════════════════════════════════════════════════════"
echo "$SUMMARY"

if [[ $FAIL -gt 0 ]]; then
    echo "❌ STABILIZE TEST FAILED"
    exit 1
else
    echo "✅ ALL STABILIZE TESTS PASSED"
    exit 0
fi
