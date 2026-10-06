<?php
declare(strict_types=1);

// CHAPTER 5.3.4 - Pricing Rules Implementation
class PricingRule
{
    public static function getAll(): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT * FROM pricing_rules ORDER BY priority DESC, rule_name ASC';
        $stmt = $pdo->query($sql);

        return $stmt->fetchAll();
    }

    public static function getActive(): array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT * FROM pricing_rules WHERE rule_status = :rule_status ORDER BY priority DESC, rule_name ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':rule_status' => 'Active']);

        return $stmt->fetchAll();
    }

    public static function findById(int $pricingRuleId): ?array
    {
        $pdo = get_db_connection();
        $sql = 'SELECT * FROM pricing_rules WHERE pricing_rule_id = :pricing_rule_id LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pricing_rule_id' => $pricingRuleId]);

        $rule = $stmt->fetch();
        return $rule ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = get_db_connection();
        $sql = 'INSERT INTO pricing_rules (
                    rule_name,
                    venue_type,
                    rule_type,
                    adjustment_type,
                    adjustment_value,
                    days_of_week,
                    min_hours,
                    start_date,
                    end_date,
                    priority,
                    rule_status,
                    created_at,
                    updated_at
                ) VALUES (
                    :rule_name,
                    :venue_type,
                    :rule_type,
                    :adjustment_type,
                    :adjustment_value,
                    :days_of_week,
                    :min_hours,
                    :start_date,
                    :end_date,
                    :priority,
                    :rule_status,
                    NOW(),
                    NOW()
                )';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':rule_name' => $data['rule_name'],
            ':venue_type' => $data['venue_type'],
            ':rule_type' => $data['rule_type'],
            ':adjustment_type' => $data['adjustment_type'],
            ':adjustment_value' => (float) $data['adjustment_value'],
            ':days_of_week' => $data['days_of_week'] ?? null,
            ':min_hours' => $data['min_hours'] !== null && $data['min_hours'] !== '' ? (float) $data['min_hours'] : null,
            ':start_date' => $data['start_date'] !== null && $data['start_date'] !== '' ? $data['start_date'] : null,
            ':end_date' => $data['end_date'] !== null && $data['end_date'] !== '' ? $data['end_date'] : null,
            ':priority' => (int) ($data['priority'] ?? 1),
            ':rule_status' => $data['rule_status'] ?? 'Active',
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $pricingRuleId, array $data): bool
    {
        $pdo = get_db_connection();
        $sql = 'UPDATE pricing_rules
                SET rule_name = :rule_name,
                    venue_type = :venue_type,
                    rule_type = :rule_type,
                    adjustment_type = :adjustment_type,
                    adjustment_value = :adjustment_value,
                    days_of_week = :days_of_week,
                    min_hours = :min_hours,
                    start_date = :start_date,
                    end_date = :end_date,
                    priority = :priority,
                    rule_status = :rule_status,
                    updated_at = NOW()
                WHERE pricing_rule_id = :pricing_rule_id';

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':pricing_rule_id' => $pricingRuleId,
            ':rule_name' => $data['rule_name'],
            ':venue_type' => $data['venue_type'],
            ':rule_type' => $data['rule_type'],
            ':adjustment_type' => $data['adjustment_type'],
            ':adjustment_value' => (float) $data['adjustment_value'],
            ':days_of_week' => $data['days_of_week'] ?? null,
            ':min_hours' => $data['min_hours'] !== null && $data['min_hours'] !== '' ? (float) $data['min_hours'] : null,
            ':start_date' => $data['start_date'] !== null && $data['start_date'] !== '' ? $data['start_date'] : null,
            ':end_date' => $data['end_date'] !== null && $data['end_date'] !== '' ? $data['end_date'] : null,
            ':priority' => (int) ($data['priority'] ?? 1),
            ':rule_status' => $data['rule_status'] ?? 'Active',
        ]);
    }

    public static function setStatus(int $pricingRuleId, string $status): bool
    {
        $pdo = get_db_connection();
        $sql = 'UPDATE pricing_rules SET rule_status = :rule_status, updated_at = NOW() WHERE pricing_rule_id = :pricing_rule_id';
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':pricing_rule_id' => $pricingRuleId,
            ':rule_status' => $status,
        ]);
    }
}
