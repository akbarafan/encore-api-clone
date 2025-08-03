

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Schedule Details -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>{{ $disruption->schedule->title }}
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Original Schedule</h6>
                        <p class="mb-1">
                            <i class="fas fa-calendar me-2"></i>{{ $disruption->schedule->date->format('l, M d, Y') }}
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-clock me-2"></i>{{ $disruption->schedule->start_time }} - {{ $disruption->schedule->end_time }}
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-user me-2"></i>{{ $disruption->instructorLogHour->instructor->first_name }} {{ $disruption->instructorLogHour->instructor->last_name }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Reason for Change</h6>
                        <div class="alert alert-info mb-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Instructor {{ $disruption->instructorLogHour->getActivityTypeLabel() }}: {{ $disruption->instructorLogHour->clock_in_notes ?? 'No additional notes' }}
                        </div>
                        <small class="text-danger">
                            <i class="fas fa-clock me-1"></i>
                            Response deadline: {{ $disruption->response_deadline->format('M d, Y g:i A') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Proposed Change -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Proposed Change
                </h5>
            </div>
            <div class="card-body">
                @if($disruption->disruption_type === 'reschedule')
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-calendar-alt me-2"></i>Reschedule Class
                        </h6>
                        @if($disruption->proposed_new_date)
                            <p class="mb-0">
                                <strong>New Schedule:</strong><br>
                                {{ \Carbon\Carbon::parse($disruption->proposed_new_date)->format('l, M d, Y') }}<br>
                                {{ $disruption->proposed_new_start_time }} - {{ $disruption->proposed_new_end_time }}
                            </p>
                        @else
                            <p class="mb-0">The class will be rescheduled to a new date and time to be determined.</p>
                        @endif
                    </div>
                @elseif($disruption->disruption_type === 'replace_instructor')
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">
                            <i class="fas fa-user-plus me-2"></i>Replace Instructor
                        </h6>
                        @if($disruption->replacementInstructor)
                            <p class="mb-0">
                                <strong>New Instructor:</strong> {{ $disruption->replacementInstructor->first_name }} {{ $disruption->replacementInstructor->last_name }}<br>
                                The class will proceed as scheduled with the replacement instructor.
                            </p>
                        @else
                            <p class="mb-0">A replacement instructor will be assigned to take the class as scheduled.</p>
                        @endif
                    </div>
                @else
                    <div class="alert alert-danger">
                        <h6 class="alert-heading">
                            <i class="fas fa-times me-2"></i>Cancel Class
                        </h6>
                        <p class="mb-0">This class will be cancelled and may be rescheduled at a later date.</p>
                    </div>
                @endif

                @if($disruption->notes)
                    <div class="mt-3">
                        <h6>Additional Notes:</h6>
                        <p class="text-muted">{{ $disruption->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        @if($hasResponded)
            <!-- Already Responded -->
            @php $response = $disruption->studentResponses->first(); @endphp
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>Your Response
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <h6 class="alert-heading">Response Recorded</h6>
                        <p>You responded on {{ $response->responded_at->format('M d, Y g:i A') }}</p>

                        @if($response->choice === 'cancel')
                            <span class="badge bg-danger">
                                <i class="fas fa-times me-1"></i>🔴 Cancel - Get full refund
                            </span>
                            <p class="mt-2 mb-0">You chose to cancel and get a full refund.</p>
                        @elseif($response->choice === 'reschedule')
                            <span class="badge bg-warning">
                                <i class="fas fa-calendar me-1"></i>🟡 Reschedule - Pick new date/time
                            </span>
                            <p class="mt-2 mb-0">
                                You chose to reschedule.<br>
                                @if($response->preferred_date)
                                    Preferred date: {{ \Carbon\Carbon::parse($response->preferred_date)->format('M d, Y') }}<br>
                                    Time: {{ $response->preferred_start_time }} - {{ $response->preferred_end_time }}
                                @endif
                            </p>
                        @else
                            <span class="badge bg-success">
                                <i class="fas fa-user-plus me-1"></i>🟢 Replace - Continue with substitute
                            </span>
                            <p class="mt-2 mb-0">You chose to continue with a substitute instructor.</p>
                        @endif

                        @if($response->notes)
                            <div class="mt-2">
                                <strong>Your Notes:</strong> {{ $response->notes }}
                            </div>
                        @endif
                    </div>

                    @if(now() <= $disruption->response_deadline)
                        <div class="text-center">
                            <button type="button" class="btn btn-outline-primary" onclick="showEditForm()">
                                <i class="fas fa-edit me-2"></i>Update Response
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <!-- Response Form -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-reply me-2"></i>Your Response
                    </h5>
                </div>
                <div class="card-body">
                    @if(now() > $disruption->response_deadline)
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            The response deadline has passed. You can no longer respond to this schedule change.
                        </div>
                    @else
                        <form id="responseForm" method="POST" action="{{ route('student.schedule-responses.store', $disruption->id) }}">
                            @csrf

                            <div class="mb-4">
                                <label class="form-label">Your Decision <span class="text-danger">*</span></label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="choice" id="cancel" value="cancel" required>
                                            <label class="form-check-label" for="cancel">
                                                <i class="fas fa-times text-danger me-1"></i>
                                                <strong>🔴 Cancel</strong><br>
                                                <small class="text-muted">Get full refund</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="choice" id="reschedule" value="reschedule" required>
                                            <label class="form-check-label" for="reschedule">
                                                <i class="fas fa-calendar text-warning me-1"></i>
                                                <strong>🟡 Reschedule</strong><br>
                                                <small class="text-muted">Pick new date/time</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="choice" id="replace" value="replace_instructor" required>
                                            <label class="form-check-label" for="replace">
                                                <i class="fas fa-user-plus text-success me-1"></i>
                                                <strong>🟢 Replace</strong><br>
                                                <small class="text-muted">Continue with substitute</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Reschedule Fields -->
                            <div id="reschedule-fields" style="display: none;">
                                <hr>
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-calendar me-2"></i>Your Preferred Schedule
                                </h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="preferred_date" class="form-label">Preferred Date</label>
                                        <input type="date" class="form-control" name="preferred_date" id="preferred_date" min="{{ date('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="preferred_start_time" class="form-label">Start Time</label>
                                        <input type="time" class="form-control" name="preferred_start_time" id="preferred_start_time">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="preferred_end_time" class="form-label">End Time</label>
                                        <input type="time" class="form-control" name="preferred_end_time" id="preferred_end_time">
                                    </div>
                                </div>
                            </div>

                            <!-- Replace Instructor Fields -->
                            <div id="replace-fields" style="display: none;">
                                <hr>
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-user-plus me-2"></i>Instructor Preference
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="preferred_replacement_instructor_id" class="form-label">Preferred Replacement Instructor (Optional)</label>
                                        <select class="form-control" name="preferred_replacement_instructor_id" id="preferred_replacement_instructor_id">
                                            <option value="">No preference</option>
                                            @foreach(\App\Models\Instructor::where('status', 'active')->get() as $instructor)
                                                <option value="{{ $instructor->id }}">{{ $instructor->first_name }} {{ $instructor->last_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Additional Comments (Optional)</label>
                                <textarea class="form-control" name="notes" id="notes" rows="3"
                                          placeholder="Any additional comments or concerns..."></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Response
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        <!-- Back Button -->
        <div class="text-center mt-4">
            <a href="{{ route('student.schedule-responses.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Schedule Responses
            </a>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Show/hide choice-specific fields based on selection
    $('input[name="choice"]').on('change', function() {
        const choice = $(this).val();
        
        // Hide all conditional fields first
        $('#reschedule-fields, #replace-fields').slideUp();
        $('#preferred_date, #preferred_start_time, #preferred_end_time').prop('required', false);
        $('#preferred_replacement_instructor_id').prop('required', false);
        
        // Show relevant fields based on choice
        if (choice === 'reschedule') {
            $('#reschedule-fields').slideDown();
            $('#preferred_date, #preferred_start_time, #preferred_end_time').prop('required', true);
        } else if (choice === 'replace_instructor') {
            $('#replace-fields').slideDown();
        }
    });

    // Form validation
    $('#responseForm').on('submit', function(e) {
        const choice = $('input[name="choice"]:checked').val();

        if (choice === 'reschedule') {
            const date = $('#preferred_date').val();
            const startTime = $('#preferred_start_time').val();
            const endTime = $('#preferred_end_time').val();

            if (!date || !startTime || !endTime) {
                e.preventDefault();
                alert('Please fill in all preferred schedule fields for rescheduling.');
                return;
            }

            if (startTime >= endTime) {
                e.preventDefault();
                alert('End time must be after start time.');
                return;
            }
        }
    });

    @if($hasResponded && now() <= $disruption->response_deadline)
    // Show edit form function
    window.showEditForm = function() {
        // Update form action for editing
        $('#responseForm').attr('action', '{{ route("student.schedule-responses.update", $disruption->id) }}');
        $('#responseForm').append('<input type="hidden" name="_method" value="PUT">');

        // Pre-fill form with existing response
        const response = @json($response);
        $(`input[name="choice"][value="${response.choice}"]`).prop('checked', true).trigger('change');

        if (response.choice === 'reschedule') {
            $('#preferred_date').val(response.preferred_date);
            $('#preferred_start_time').val(response.preferred_start_time);
            $('#preferred_end_time').val(response.preferred_end_time);
        } else if (response.choice === 'replace_instructor') {
            $('#preferred_replacement_instructor_id').val(response.preferred_replacement_instructor_id);
        }

        $('#notes').val(response.notes);

        // Show form
        $('.card').last().find('.card-body').html($('#responseForm').parent().html());
    };
    @endif
});
</script>
