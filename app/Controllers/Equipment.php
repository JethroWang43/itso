<?php
namespace App\Controllers;

use App\Models\EquipmentModel;

class Equipment extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new EquipmentModel();
    }

    protected function normalizeItems(array $rows)
    {
        $out = [];
        foreach ($rows as $r) {
            $n = $this->model->normalize($r);
            $out[] = $n;
        }
        return $out;
    }

    public function index()
    {
        // fetch all for stats
        $all = $this->model->findAll();
        $total = count($all);
        $available = count(array_filter($all, fn($i) => strtolower($i['status'] ?? '') === 'available'));
        $borrowed = count(array_filter($all, fn($i) => strtolower($i['status'] ?? '') === 'borrowed'));
        $maintenance = count(array_filter($all, fn($i) => strtolower($i['status'] ?? '') === 'maintenance'));
        $reserved = count(array_filter($all, fn($i) => strtolower($i['status'] ?? '') === 'reserved'));

        // support search and category filter
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $category = trim((string) ($this->request->getGet('category') ?? ''));
        $statusFilter = trim((string) ($this->request->getGet('status') ?? ''));

        // pagination
        $perPage = 6;
        $page = (int) ($this->request->getGet('page') ?? 1);
        if ($page < 1)
            $page = 1;
        $offset = ($page - 1) * $perPage;

        // build query with optional filters
        $builder = $this->model->builder();
        if ($q !== '') {
            $builder->groupStart()
                ->like('equipment_id', $q)
                ->orLike('name', $q)
                ->orLike('description', $q)
                ->orLike('category', $q)
                ->groupEnd();
        }
        if ($category !== '' && $category !== 'all') {
            $builder->where('category', $category);
        }

        // Status filter: if provided and not 'all', match the status (case-insensitive)
        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $normalized = ucfirst(strtolower($statusFilter));
            $builder->where('status', $normalized);
        }

        $totalFiltered = (int) $builder->countAllResults(false);

        $pagedRows = $builder->orderBy('idequipment', 'ASC')->get($perPage, $offset)->getResultArray();
        $items = $this->normalizeItems($pagedRows);

        $data = [
            'title' => 'EMS - Equipment',
            'items' => $items,
            'total' => $total,
            'available' => $available,
            'borrowed' => $borrowed,
            'maintenance' => $maintenance,
            'reserve' => $reserved,
            'page' => $page,
            'perPage' => $perPage,
            'q' => $q,
            'category' => $category,
            'status' => $statusFilter,
            'totalFiltered' => $totalFiltered
        ];

        return view('include\\head_view', $data)
            . view('include\\nav_view')
            . view('equipmentlist_view', $data)
            . view('include\\foot_view');
    }

    public function add()
    {
        $data = ['title' => 'EMS - Add Equipment'];

        return view('include\\head_view', $data)
            . view('include\\nav_view')
            . view('addequipment_view', $data)
            . view('include\\foot_view');
    }

    // equipment.php - inside the Equipment class

    public function insert()
    {
        $now = date('Y-m-d H:i:s');
        $data = [
            'equipment_id' => $this->request->getPost('equipment_id') ?: 'EQ-'.time(),
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'category' => $this->request->getPost('category'),
            'status' => $this->request->getPost('status') ?: 'Available',
            'location' => $this->request->getPost('location'),
            'last_updated' => $now,
        ];

        // Validation rules/messages
        $rules = [
            'equipment_id' => 'permit_empty|alpha_dash',
            'name' => 'required|min_length[3]',
            'description' => 'permit_empty',
            'category' => 'required',
            'status' => 'required|in_list[Available,Borrowed,Maintenance,Reserved]',
            'location' => 'required|min_length[2]',
        ];

        $messages = [
            'equipment_id' => ['alpha_dash' => 'Equipment ID may contain only letters, numbers, dashes and underscores.'],
            'name' => ['required' => 'Please enter a name.', 'min_length' => 'Please enter a name (at least 3 characters).'],
            'category' => ['required' => 'Please select a category.'],
            'status' => ['required' => 'Please select a status.', 'in_list' => 'Invalid status selected.'],
            'location' => ['required' => 'Location is required.'],
        ];

        if (! $this->validate($rules, $messages)) {
            session()->setFlashdata('errors', $this->validator->getErrors());
            return redirect()->to('equipment/add')->withInput();
        }

        $this->model->insert($data);
        session()->setFlashdata('success', 'Adding new equipment is successful.');

        // 7. Flash success message
        session()->setFlashdata('success', 'Equipment added successfully.');

        // 8. Redirect on success
        return redirect()->to('equipment');
    }

    public function view($id)
    {
        $row = $this->model->find($id);
        if (!$row)
            return redirect()->to('equipment');

        $item = $this->model->normalize($row);

        $data = ['title' => 'EMS - View Equipment', 'item' => $item];

        return view('include\\head_view', $data)
            . view('include\\nav_view')
            . view('viewequipment_view', $data)
            . view('include\\foot_view');
    }

    public function edit($id)
    {
        $row = $this->model->find($id);
        if (!$row)
            return redirect()->to('equipment');

        $item = $this->model->normalize($row);

        // Allow pre-filling the status via query parameter (e.g. ?prefill=Borrowed)
        $prefill = $this->request->getGet('prefill');
        if (!empty($prefill)) {
            $allowed = ['Available', 'Borrowed', 'Maintenance', 'Reserved'];
            // normalize case-insensitive
            foreach ($allowed as $a) {
                if (strcasecmp($a, $prefill) === 0) {
                    $item['status'] = $a;
                    // provide a small flash message so the user knows this was prefilled
                    session()->setFlashdata('info', 'Status prefilled: ' . $a . '. Save to apply.');
                    break;
                }
            }
        }

        $data = ['title' => 'EMS - Edit Equipment', 'item' => $item];

        return view('include\\head_view', $data)
            . view('include\\nav_view')
            . view('updateequipment_view', $data)
            . view('include\\foot_view');
    }

    public function update($id)
    {
        $now = date('Y-m-d H:i:s');
        $data = [
            'equipment_id' => $this->request->getPost('equipment_id'),
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'category' => $this->request->getPost('category'),
            'status' => $this->request->getPost('status'),
            'location' => $this->request->getPost('location'),
            'last_updated' => $now,
        ];

        $rules = [
            'equipment_id' => 'permit_empty|alpha_dash',
            'name' => 'required|min_length[3]',
            'description' => 'permit_empty',
            'category' => 'required',
            'status' => 'required|in_list[Available,Borrowed,Maintenance,Reserved]',
            'location' => 'required|min_length[2]',
        ];

        $messages = [
            'name' => ['required' => 'Please enter a name.'],
            'category' => ['required' => 'Please select a category.'],
            'status' => ['required' => 'Please select a status.'],
            'location' => ['required' => 'Location is required.'],
        ];

        if (! $this->validate($rules, $messages)) {
            session()->setFlashdata('errors', $this->validator->getErrors());
            return redirect()->to('equipment/edit/' . $id)->withInput();
        }

        $this->model->update($id, $data);
        session()->setFlashdata('success', 'Equipment updated successfully.');

        return redirect()->to('equipment');
    }

    public function delete($id)
    {
        // delete from DB
        $this->model->delete($id);
        return redirect()->to('equipment');
    }
}

?>
