<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/BaseApiController.php';

class ReservationController extends BaseApiController
{
    private const STATUSES = ['pending', 'confirmed', 'cancelled'];

    /** Public: guests request a table. */
    public function store()
    {
        $this->api->require_method('POST');
        $this->limit('reserve', 5, 300);
        $in = $this->json_body();

        $name  = trim((string) ($in['guest_name'] ?? ''));
        $email = trim((string) ($in['email'] ?? ''));
        $phone = trim((string) ($in['phone'] ?? ''));
        $date  = trim((string) ($in['reserve_date'] ?? ''));
        $time  = trim((string) ($in['reserve_time'] ?? ''));
        $party = (int) ($in['party_size'] ?? 0);
        $note  = trim((string) ($in['note'] ?? ''));

        $errors = [];
        if ($name === '' || $this->len($name) > 100) $errors['guest_name'] = 'Enter your name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
        if ($this->len($phone) > 30) $errors['phone'] = 'Phone is too long.';
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date || $date < date('Y-m-d')) $errors['reserve_date'] = 'Pick a date from today onward.';
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) $errors['reserve_time'] = 'Pick a time.';
        if ($party < 1 || $party > 20) $errors['party_size'] = 'Party size must be 1 to 20.';
        if ($this->len($note) > 500) $errors['note'] = 'Keep the note under 500 characters.';

        if ($errors) {
            $this->fail('Please fix the highlighted fields.', 422, ['fields' => $errors]);
        }

        $this->db->raw(
            'INSERT INTO reservations (guest_name, email, phone, reserve_date, reserve_time, party_size, note) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$name, $email, $phone, $date, $time . ':00', $party, $note]
        );
        $this->ok(['message' => 'Table request received. We will confirm by email.'], 201);
    }

    public function index()
    {
        $this->api->require_method('GET');
        $this->auth('read');
        $rows = $this->db->raw(
            'SELECT id, guest_name, email, phone, reserve_date, TIME_FORMAT(reserve_time, "%H:%i") AS reserve_time, party_size, note, status, created_at FROM reservations ORDER BY reserve_date ASC, reserve_time ASC'
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['id'] = (int) $r['id'];
            $r['party_size'] = (int) $r['party_size'];
        }
        $this->ok(['data' => $rows, 'count' => count($rows)]);
    }

    public function update($id)
    {
        $this->auth('write');
        $in = $this->json_body();
        $status = (string) ($in['status'] ?? '');
        if (!in_array($status, self::STATUSES, true)) {
            $this->fail('Status must be pending, confirmed or cancelled.', 422);
        }
        $stmt = $this->db->raw('UPDATE reservations SET status = ? WHERE id = ?', [$status, (int) $id]);
        $exists = $this->db->raw('SELECT id FROM reservations WHERE id = ?', [(int) $id])->fetch(PDO::FETCH_ASSOC);
        if (!$exists) {
            $this->fail('Reservation not found.', 404);
        }
        $this->ok(['message' => 'Reservation ' . $status]);
    }

    public function destroy($id)
    {
        $this->api->require_method('DELETE');
        $this->auth('delete');
        $this->db->raw('DELETE FROM reservations WHERE id = ?', [(int) $id]);
        $this->ok(['message' => 'Reservation deleted']);
    }
}
