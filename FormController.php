<?php

namespace YourUsername\FormBuilder\Http\Controllers;

use YourUsername\FormBuilder\Models\Form;
use YourUsername\FormBuilder\Models\FormSubmission;
use YourUsername\FormBuilder\Mail\FormSubmissionNotification;
use YourUsername\FormBuilder\Mail\FormSubmissionConfirmation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class FormController extends Controller
{
    /**
     * Display a listing of forms (admin view)
     */
    public function index()
    {
        $forms = Form::withCount('submissions')->latest()->get();
        return view('form-builder::forms.index', compact('forms'));
    }

    /**
     * Show the form for creating a new form
     */
    public function create()
    {
        return view('form-builder::forms.create');
    }

    /**
     * Store a newly created form
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'staff_email' => 'required|email',
            'confirmation_message' => 'nullable|string',
            'send_confirmation' => 'boolean',
            'fields' => 'required|array|min:1',
            'fields.*.name' => 'required|string',
            'fields.*.label' => 'required|string',
            'fields.*.type' => 'required|string|in:' . implode(',', config('form-builder.field_types', [])),
            'fields.*.required' => 'boolean',
            'fields.*.options' => 'nullable|array',
        ]);

        $form = Form::create($validated);

        return redirect()->route(config('form-builder.routes.name_prefix') . 'edit', $form)
            ->with('success', 'Form created successfully! Share this URL: ' . $form->url);
    }

    /**
     * Display the public form (for users to fill out)
     */
    public function show($slug)
    {
        $form = Form::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return view('form-builder::forms.show', compact('form'));
    }

    /**
     * Show the form for editing
     */
    public function edit(Form $form)
    {
        return view('form-builder::forms.edit', compact('form'));
    }

    /**
     * Update the specified form
     */
    public function update(Request $request, Form $form)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'staff_email' => 'required|email',
            'confirmation_message' => 'nullable|string',
            'send_confirmation' => 'boolean',
            'fields' => 'required|array|min:1',
            'fields.*.name' => 'required|string',
            'fields.*.label' => 'required|string',
            'fields.*.type' => 'required|string|in:' . implode(',', config('form-builder.field_types', [])),
            'fields.*.required' => 'boolean',
            'fields.*.options' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $form->update($validated);

        return redirect()->route(config('form-builder.routes.name_prefix') . 'edit', $form)
            ->with('success', 'Form updated successfully!');
    }

    /**
     * Remove the specified form
     */
    public function destroy(Form $form)
    {
        $form->delete();
        return redirect()->route(config('form-builder.routes.name_prefix') . 'index')
            ->with('success', 'Form deleted successfully!');
    }

    /**
     * Submit a form (public endpoint)
     */
    public function submit(Request $request, $slug)
    {
        $form = Form::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // Validate submission against form fields
        $validator = $form->validateSubmission($request->all());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Store submission
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'data' => $validator->validated(),
            'ip_address' => config('form-builder.storage.log_ip_addresses') ? $request->ip() : null,
        ]);

        // Send email notification to staff member
        if (config('form-builder.email.enabled')) {
            try {
                $mail = Mail::to($form->staff_email);
                
                if (config('form-builder.email.queue')) {
                    $mail->queue(new FormSubmissionNotification($form, $submission));
                } else {
                    $mail->send(new FormSubmissionNotification($form, $submission));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send staff notification: ' . $e->getMessage());
            }
        }

        // Send confirmation email to submitter if they provided an email and confirmation is enabled
        if ($form->send_confirmation && config('form-builder.email.enabled')) {
            $submitterEmail = $this->findEmailInSubmission($form, $validator->validated());
            
            if ($submitterEmail) {
                try {
                    $mail = Mail::to($submitterEmail);
                    
                    if (config('form-builder.email.queue')) {
                        $mail->queue(new FormSubmissionConfirmation($form, $submission));
                    } else {
                        $mail->send(new FormSubmissionConfirmation($form, $submission));
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send confirmation email: ' . $e->getMessage());
                }
            }
        }

        return redirect()->back()->with('success', 'Form submitted successfully! Check your email for confirmation.');
    }

    /**
     * Find email field in submission data
     */
    private function findEmailInSubmission($form, $data)
    {
        foreach ($form->fields as $field) {
            if ($field['type'] === 'email' && isset($data[$field['name']])) {
                return $data[$field['name']];
            }
        }
        return null;
    }

    /**
     * View submissions for a form
     */
    public function submissions(Form $form)
    {
        $submissions = $form->submissions()->latest()->paginate(20);
        return view('form-builder::forms.submissions', compact('form', 'submissions'));
    }

    /**
     * View statistics for a form
     */
    public function statistics(Form $form)
    {
        if (!config('form-builder.statistics.enabled')) {
            abort(404);
        }

        $submissions = $form->submissions()->get();
        $totalSubmissions = $submissions->count();

        // Calculate statistics
        $statistics = [
            'total' => $totalSubmissions,
            'today' => $form->submissions()->whereDate('created_at', today())->count(),
            'this_week' => $form->submissions()
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'this_month' => $form->submissions()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'average_per_day' => $totalSubmissions > 0 
                ? round($totalSubmissions / max(1, $form->created_at->diffInDays(now()) ?: 1), 2)
                : 0,
        ];

        // Submissions by date (configurable days)
        $daysToShow = config('form-builder.statistics.days_to_show', 30);
        $submissionsByDate = $form->submissions()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays($daysToShow))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('count', 'date');

        // Field statistics
        $fieldStats = [];
        foreach ($form->fields as $field) {
            if (in_array($field['type'], ['select', 'radio', 'checkbox'])) {
                $fieldName = $field['name'];
                $values = $submissions->pluck("data.{$fieldName}")->filter();
                
                $counts = [];
                foreach ($values as $value) {
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            $counts[$v] = ($counts[$v] ?? 0) + 1;
                        }
                    } else {
                        $counts[$value] = ($counts[$value] ?? 0) + 1;
                    }
                }
                
                arsort($counts);
                
                $fieldStats[$fieldName] = [
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'counts' => $counts,
                    'total_responses' => array_sum($counts),
                ];
            }
        }

        return view('form-builder::forms.statistics', compact('form', 'statistics', 'submissionsByDate', 'fieldStats'));
    }

    /**
     * Export submissions as JSON
     */
    public function export(Form $form)
    {
        $submissions = $form->submissions()->get();
        
        return response()->json([
            'form' => [
                'title' => $form->title,
                'slug' => $form->slug,
            ],
            'submissions' => $submissions->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'data' => $submission->data,
                    'submitted_at' => $submission->created_at,
                ];
            }),
        ])->header('Content-Disposition', 'attachment; filename="' . $form->slug . '-submissions.json"');
    }
}
