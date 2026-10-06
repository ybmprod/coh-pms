<?php
declare(strict_types=1);

// CHAPTER 5.3.2 - Venue Management
class VenueController extends Controller
{
    public function index(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER])) {
            return;
        }

        $venues = Venue::getAll();
        $this->render('staff/venues', [
            'pageTitle' => 'Venue Management',
            'venues' => $venues,
        ]);
    }

    public function create(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER])) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/?r=venue/index');
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for venue/create.');
            set_flash('error', 'Your session has expired.');
            $this->redirect(BASE_URL . '/?r=venue/index');
        }

        $validation = $this->validateVenue($_POST);
        if ($validation['errors'] !== []) {
            set_flash('error', implode(' ', $validation['errors']));
            $this->redirect(BASE_URL . '/?r=venue/index');
        }

        $data = $validation['data'];
        $savedImage = null;
        if (!empty($_FILES['image']['name'])) {
            $savedImage = save_uploaded_venue_image($_FILES['image']);
            if ($savedImage === null) {
                set_flash('error', 'Image upload failed. Use JPG, PNG, or WEBP under 2MB.');
                $this->redirect(BASE_URL . '/?r=venue/index');
            }
        }

        $data['image_path'] = $savedImage;
        try {
            Venue::create($data);
        } catch (Throwable $exception) {
            $this->deleteVenueImage($savedImage);
            error_log('Venue creation failed: ' . $exception->getMessage());
            set_flash('error', 'Venue could not be saved. Please try again.');
            $this->redirect(BASE_URL . '/?r=venue/index');
        }

        set_flash('success', 'Venue created successfully.');
        $this->redirect(BASE_URL . '/?r=venue/index');
    }

    public function edit(): void
    {
        if ($this->denyIfNotRole([ROLE_ADMINISTRATOR, ROLE_BOOKING_OFFICER])) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/?r=venue/index');
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            error_log('CSRF validation failed for venue/edit.');
            set_flash('error', 'Your session has expired.');
            $this->redirect(BASE_URL . '/?r=venue/index');
        }

        $venueId = (int) ($_POST['venue_id'] ?? 0);
        $venue = Venue::findById($venueId);

        if (!$venue) {
            set_flash('error', 'Venue was not found.');
            $this->redirect(BASE_URL . '/?r=venue/index');
        }

        $validation = $this->validateVenue($_POST);
        if ($validation['errors'] !== []) {
            set_flash('error', implode(' ', $validation['errors']));
            $this->redirect(BASE_URL . '/?r=venue/index');
        }

        $data = $validation['data'];
        $oldImagePath = $venue['image_path'];
        $savedImage = null;
        if (!empty($_FILES['image']['name'])) {
            $savedImage = save_uploaded_venue_image($_FILES['image']);
            if ($savedImage === null) {
                set_flash('error', 'Image upload failed. Use JPG, PNG, or WEBP under 2MB.');
                $this->redirect(BASE_URL . '/?r=venue/index');
            }
        }

        $data['image_path'] = $savedImage ?? $oldImagePath;
        try {
            if (!Venue::update($venueId, $data)) {
                throw new RuntimeException('Venue update did not complete.');
            }
        } catch (Throwable $exception) {
            $this->deleteVenueImage($savedImage);
            error_log('Venue update failed: ' . $exception->getMessage());
            set_flash('error', 'Venue could not be updated. Please try again.');
            $this->redirect(BASE_URL . '/?r=venue/index');
        }

        if ($savedImage !== null && $oldImagePath !== $savedImage) {
            $this->deleteVenueImage($oldImagePath);
        }

        set_flash('success', 'Venue details updated successfully.');
        $this->redirect(BASE_URL . '/?r=venue/index');
    }

    public function customerList(): void
    {
        if (!$this->requireAuth()) {
            $this->redirect(BASE_URL . '/?r=auth/login');
        }

        $venues = Venue::getActive();
        $this->render('customer/venues', [
            'pageTitle' => 'Available Venues',
            'venues' => $venues,
        ]);
    }

    public function details(): void
    {
        if (!$this->requireAuth()) {
            $this->redirect(BASE_URL . '/?r=auth/login');
        }

        $venueId = (int) ($_GET['id'] ?? 0);
        $venue = Venue::findById($venueId);

        if (!$venue) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => 'Venue not found']);
            return;
        }

        $this->render('customer/venue_details', [
            'pageTitle' => $venue['venue_name'],
            'venue' => $venue,
        ]);
    }

    private function validateVenue(array $input): array
    {
        $venueName = trim((string) ($input['venue_name'] ?? ''));
        $venueType = trim((string) ($input['venue_type'] ?? ''));
        $location = trim((string) ($input['location'] ?? ''));
        $capacityValue = trim((string) ($input['capacity'] ?? ''));
        $priceValue = trim((string) ($input['standard_price'] ?? ''));
        $venueStatus = trim((string) ($input['venue_status'] ?? 'Available'));
        $errors = [];

        if ($venueName === '') {
            $errors[] = 'Venue name is required.';
        }
        if (!in_array($venueType, ['Community Hall', 'Community Centre', 'Stadium', 'Open Space', 'Other'], true)) {
            $errors[] = 'Venue type is invalid.';
        }
        if ($location === '') {
            $errors[] = 'Location is required.';
        }
        if (filter_var($capacityValue, FILTER_VALIDATE_INT) === false || (int) $capacityValue <= 0) {
            $errors[] = 'Capacity must be a whole number greater than zero.';
        }
        if (!is_numeric($priceValue) || (float) $priceValue < 0) {
            $errors[] = 'Standard price must be zero or greater.';
        }
        if (!in_array($venueStatus, ['Available', 'Unavailable', 'Under Maintenance'], true)) {
            $errors[] = 'Venue status is invalid.';
        }

        return [
            'data' => [
                'venue_name' => $venueName,
                'venue_type' => $venueType,
                'location' => $location,
                'capacity' => (int) $capacityValue,
                'facilities' => trim((string) ($input['facilities'] ?? '')),
                'description' => trim((string) ($input['description'] ?? '')),
                'standard_price' => is_numeric($priceValue) ? (float) $priceValue : 0.0,
                'venue_status' => $venueStatus,
            ],
            'errors' => $errors,
        ];
    }

    private function deleteVenueImage(?string $imagePath): void
    {
        if ($imagePath === null || $imagePath === '') {
            return;
        }

        $filename = basename(str_replace('\\', '/', $imagePath));
        $path = APP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}
