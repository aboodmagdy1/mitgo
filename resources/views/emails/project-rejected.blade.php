<x-mail::message>
# Project Rejected

We regret to inform you that your project submission <strong>{{ $project->name }}</strong> has been rejected.

**Reason for rejection:**

{{ $reason }}

If you have any questions, please contact support.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
