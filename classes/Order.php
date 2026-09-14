<?php

class Order
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $query = "
            SELECT
                o.id,
                o.customer_id,
                o.total_amount,

                -- Pickup / Delivery details
                o.delivery_method,
                o.delivery_address,
                o.delivery_contact,
                o.delivery_landmark,
                o.delivery_notes,

                o.status,
                o.created_at,

                c.full_name,
                c.email,
                c.contact_number

            FROM orders o

            INNER JOIN customers c
                ON c.id = o.customer_id

            ORDER BY o.id DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getItemsByOrderId(int $order_id): array
    {
        $query = "
            SELECT
                oi.product_id,
                oi.quantity,
                oi.price,
                oi.subtotal,
                COALESCE(
                    p.product_name,
                    'Deleted Product'
                ) AS product_name

            FROM order_items oi

            LEFT JOIN products p
                ON p.id = oi.product_id

            WHERE oi.order_id = :order_id

            ORDER BY oi.id ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(
            ":order_id",
            $order_id,
            PDO::PARAM_INT
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(
        int $order_id,
        string $status
    ): bool {
        $query = "
            UPDATE orders
            SET status = :status
            WHERE id = :id
        ";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(
            ":status",
            $status
        );

        $stmt->bindParam(
            ":id",
            $order_id,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }
}