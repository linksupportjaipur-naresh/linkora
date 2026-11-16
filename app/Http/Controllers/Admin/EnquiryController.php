<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    public const STATUSES = ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'closed' => 'Closed'];

    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));

        $enquiries = Enquiry::query()
            ->when(array_key_exists((string) $status, self::STATUSES), fn ($w) => $w->where('status', $status))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('subject', 'like', "%{$q}%")->orWhere('message', 'like', "%{$q}%")))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $counts = Enquiry::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.enquiries.index', compact('enquiries', 'status', 'q', 'counts'));
    }

    public function show(Enquiry $enquiry)
    {
        if ($enquiry->status === Enquiry::STATUS_NEW) {
            $enquiry->forceFill(['status' => 'read'])->save();
        }

        return view('admin.enquiries.show', compact('enquiry'));
    }

    public function update(Request $request, Enquiry $enquiry)
    {
        $data = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(self::STATUSES))]]);
        $enquiry->forceFill($data)->save();

        return back()->with('status', 'Enquiry marked as ' . strtolower(self::STATUSES[$data['status']]) . '.');
    }

    public function destroy(Enquiry $enquiry)
    {
        $enquiry->delete();

        return redirect()->route('admin.enquiries.index')->with('status', 'Enquiry deleted.');
    }
}
