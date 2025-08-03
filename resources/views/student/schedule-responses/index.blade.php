@extends('student.layouts.app')

@section('title', 'Schedule Changes')
@section('page-title', 'Schedule Changes')
@section('page-subtitle', 'Please respond to schedule changes that require your attention')

@section('content')
<div class="container-fluid">
    <!-- Alert Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Pending Responses -->
    @if($pendingDisruptions->isNotEmpty())
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock text-warning me-2"></i>Pending Responses
                        <span class="badge bg-warning text-dark ms-2">{{ $pendingDisruptions->count() }} Pending</span>
                    </h5>
                    <p class="text-muted small mb-0">Please respond to these schedule changes</p>
                </div>
                <div class="card-body">
                    @foreach($pendingDisruptions as $disruption)
                    <div class="card schedule-card mb-3 border-start border-warning border-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <!-- Class Info -->
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="me-3">
                                            <i class="fas fa-chalkboard-teacher text-primary fa-2x"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1">{{ $disruption->schedule->class->name }} - {{ $disruption->schedule->title }}</h5>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-calendar me-1"></i>{{ $disruption->schedule->date->format('l, F j, Y') }}<br>
                                                <i class="fas fa-clock me-1"></i>{{ Carbon\Carbon::parse($disruption->schedule->start_time)->format('H:i') }} - {{ Carbon\Carbon::parse($disruption->schedule->end_time)->format('H:i') }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Reason for Change -->
                                    <div class="reason-box p-3 rounded mb-4" style="background: #f8f9fa; border-left: 4px solid #ffc107;">
                                        <h6 class="text-dark mb-2">
                                            <i class="fas fa-info-circle text-warning me-2"></i>Reason for Schedule Change
                                        </h6>
                                        <p class="mb-2">
                                        <strong>{{ $disruption->reason }}</strong>
                                        </p>
                                        @if($disruption->instructorLogHour->clock_in_notes)
                                        <small class="text-muted">
                                            <i class="fas fa-sticky-note me-1"></i>Additional notes: {{ $disruption->instructorLogHour->clock_in_notes }}
                                        </small>
                                        @endif
                                    </div>

                                    <!-- Response Form -->
                                    <form action="{{ route('student.schedule-responses.store', $disruption->id) }}" method="POST" id="responseForm{{ $disruption->id }}">
                                        @csrf
                                        <div class="row g-3 mb-4">
                                            <!-- Cancel Option -->
                                            <div class="col-md-4">
                                            <div class="card h-100 border-danger choice-card" data-choice="cancel" onclick="selectChoice('cancel', {{ $disruption->id }})">
                                            <div class="card-body text-center">
                                            <input type="radio" name="choice" value="cancel" id="cancel{{ $disruption->id }}" class="d-none choice-radio" onchange="enableSubmitButton({{ $disruption->id }})">
                                            <i class="fas fa-times-circle text-danger fa-2x mb-2"></i>
                                            <h6 class="card-title">Cancel Class</h6>
                                            <p class="card-text small text-muted">Skip this session entirely</p>
                                            <strong class="text-danger">Full Refund</strong><br>
                                            <small class="text-muted">No charge for this class</small>
                                            </div>
                                            </div>
                                            </div>

                                            <!-- Reschedule Option -->
                                            <div class="col-md-4">
                                            <div class="card h-100 border-warning choice-card" data-choice="reschedule" onclick="selectChoice('reschedule', {{ $disruption->id }})">
                                            <div class="card-body text-center">
                                            <input type="radio" name="choice" value="reschedule" id="reschedule{{ $disruption->id }}" class="d-none choice-radio" onchange="enableSubmitButton({{ $disruption->id }})">
                                            <i class="fas fa-calendar-alt text-warning fa-2x mb-2"></i>
                                            <h6 class="card-title">Reschedule</h6>
                                            <p class="card-text small text-muted">Move to different date/time</p>
                                            <strong class="text-warning">Pick New Time</strong><br>
                                            <small class="text-muted">Choose preferred schedule</small>
                                            </div>
                                            </div>
                                            </div>

                                            <!-- Replace Instructor Option -->
                                            <div class="col-md-4">
                                            <div class="card h-100 border-success choice-card" data-choice="replace_instructor" onclick="selectChoice('replace_instructor', {{ $disruption->id }})">
                                            <div class="card-body text-center">
                                            <input type="radio" name="choice" value="replace_instructor" id="replace{{ $disruption->id }}" class="d-none choice-radio" onchange="enableSubmitButton({{ $disruption->id }})">
                                            <i class="fas fa-user-graduate text-success fa-2x mb-2"></i>
                                            <h6 class="card-title">Replace Instructor</h6>
                                            <p class="card-text small text-muted">Continue with substitute</p>
                                            <strong class="text-success">Same Schedule</strong><br>
                                            <small class="text-muted">Different teacher</small>
                                            </div>
                                            </div>
                                            </div>
                                        </div>

                                        <!-- Additional Options for Reschedule -->
                                        <div id="rescheduleOptions{{ $disruption->id }}" class="reschedule-options d-none mb-3">
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <h6>Preferred New Schedule:</h6>
                                                    <div class="row g-2">
                                                        <div class="col-md-4">
                                                            <label class="form-label">Date</label>
                                                            <input type="date" name="preferred_date" class="form-control" min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Start Time</label>
                                                            <input type="time" name="preferred_start_time" class="form-control" value="{{ Carbon\Carbon::parse($disruption->schedule->start_time)->format('H:i') }}">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">End Time</label>
                                                            <input type="time" name="preferred_end_time" class="form-control" value="{{ Carbon\Carbon::parse($disruption->schedule->end_time)->format('H:i') }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Notes -->
                                        <div class="mb-3">
                                            <label class="form-label">Additional Notes (Optional)</label>
                                            <textarea name="notes" class="form-control" rows="2" placeholder="Any additional comments or requirements..."></textarea>
                                        </div>
                                    </form>
                                </div>

                                <div class="col-md-4">
                                    <!-- Response Deadline -->
                                    <div class="alert alert-warning mb-3">
                                        <h6 class="alert-heading mb-2">
                                            <i class="fas fa-hourglass-half me-2"></i>Response Deadline
                                        </h6>
                                        <p class="mb-2"><strong>{{ $disruption->response_deadline->format('M j, Y - g:i A') }}</strong></p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>{{ $disruption->response_deadline->diffForHumans() }}
                                        </small>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="d-grid gap-2 mb-3">
                                    <button type="submit" form="responseForm{{ $disruption->id }}" class="btn btn-secondary btn-lg submit-response" disabled>
                                    <i class="fas fa-paper-plane me-2"></i>Submit Response
                                    </button>
                                        <small class="text-muted text-center">
                                        <i class="fas fa-info-circle me-1"></i>Please select an option above
                                        </small>
                                         <button type="button" class="btn btn-sm btn-info mt-2 w-100" onclick="enableSubmitButton({{ $disruption->id }})">
                                             Debug: Force Enable
                                         </button>

                                     </div>

                                    <!-- Other Students Progress -->
                                    @php
                                    $totalStudents = $disruption->total_students ?? $disruption->schedule->class->classStudents()->where('status', 'active')->count();
                                    $responses = $disruption->responses_count ?? $disruption->studentResponses()->count();
                                    @endphp
                                    <div class="p-3 bg-light rounded">
                                    <h6 class="text-dark mb-2">
                                    <i class="fas fa-users me-2"></i>Voting Progress
                                    </h6>
                                    <div class="progress mb-2" style="height: 10px;">
                                    <div class="progress-bar" style="width: {{ $totalStudents > 0 ? ($responses / $totalStudents) * 100 : 0 }}%"></div>
                                    </div>
                                    <small class="text-muted">
                                    {{ $responses }} of {{ $totalStudents }} students responded<br>
                                    @if($disruption->vote_distribution && is_array($disruption->vote_distribution))
                                            @php
                                                    $maxVotes = max(array_values($disruption->vote_distribution));
                                                     $leadingChoice = array_keys($disruption->vote_distribution, $maxVotes)[0] ?? 'None';
                                                 @endphp
                                                 Leading: <strong>{{ ucfirst(str_replace('_', ' ', $leadingChoice)) }}</strong> ({{ $maxVotes }} votes)
                                             @else
                                                 Majority choice will be implemented
                                             @endif
                                         </small>
                                     </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Responses -->
    @if($respondedDisruptions->isNotEmpty())
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-check-circle text-success me-2"></i>Recent Responses
                    </h5>
                    <p class="text-muted small mb-0">Your previous schedule change responses</p>
                </div>
                <div class="card-body">
                    @foreach($respondedDisruptions as $disruption)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1">{{ $disruption->schedule->class->name }}</h6>
                                    <small class="text-muted">
                                        {{ $disruption->schedule->date->format('M j, Y') }} - 
                                        Your choice: <strong>{{ ucfirst(str_replace('_', ' ', $disruption->studentResponses->first()->choice ?? 'Unknown')) }}</strong>
                                    </small>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-secondary">Responded</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Empty State -->
    @if($pendingDisruptions->isEmpty() && $respondedDisruptions->isEmpty())
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-calendar-check text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="text-muted mb-2">No Schedule Changes</h5>
                    <p class="text-muted mb-0">You currently have no schedule changes that require a response.</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
.choice-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border-width: 2px !important;
    position: relative;
    user-select: none;
}

.choice-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.choice-card.selected {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.choice-card.selected.border-danger {
    background-color: #f8d7da;
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.choice-card.selected.border-warning {
    background-color: #fff3cd;
    border-color: #ffc107 !important;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
}

.choice-card.selected.border-success {
    background-color: #d1e7dd;
    border-color: #198754 !important;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

.schedule-card {
    transition: all 0.3s ease;
}

.schedule-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}

.submit-response:disabled {
    background-color: #6c757d !important;
    border-color: #6c757d !important;
    cursor: not-allowed;
    opacity: 0.6;
}

.submit-response:not(:disabled) {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
    cursor: pointer;
    opacity: 1;
}
</style>

<script>
// Simple inline functions that work directly
function selectChoice(choice, disruptionId) {
    console.log('selectChoice called:', choice, disruptionId);
    
    // Remove selection from all cards in this form
    const form = document.getElementById('responseForm' + disruptionId);
    if (form) {
        form.querySelectorAll('.choice-card').forEach(card => {
            card.classList.remove('selected');
        });
    }
    
    // Add selection to clicked card
    const clickedCard = event.target.closest('.choice-card');
    if (clickedCard) {
        clickedCard.classList.add('selected');
    }
    
    // Check the radio button
    const radio = document.getElementById(choice + disruptionId);
    if (radio) {
        radio.checked = true;
        console.log('Radio checked:', radio.checked);
    }
    
    // Show/hide reschedule options
    const rescheduleOptions = document.getElementById('rescheduleOptions' + disruptionId);
    if (rescheduleOptions) {
        if (choice === 'reschedule') {
            rescheduleOptions.classList.remove('d-none');
        } else {
            rescheduleOptions.classList.add('d-none');
        }
    }
    
    // Enable submit button
    enableSubmitButton(disruptionId);
}

function enableSubmitButton(disruptionId) {
    console.log('enableSubmitButton called:', disruptionId);
    
    const submitBtn = document.querySelector('button[form="responseForm' + disruptionId + '"]');
    const submitBtn2 = document.querySelector('#responseForm' + disruptionId + ' .submit-response');
    const helpText = document.querySelector('#responseForm' + disruptionId).parentElement.querySelector('small.text-muted');
    
    console.log('Submit button method 1:', submitBtn);
    console.log('Submit button method 2:', submitBtn2);
    console.log('Help text:', helpText);
    
    const actualBtn = submitBtn || submitBtn2;
    
    if (actualBtn) {
        actualBtn.disabled = false;
        actualBtn.classList.remove('btn-secondary');
        actualBtn.classList.add('btn-primary');
        actualBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Response';
        
        console.log('Submit button enabled successfully');
        
        if (helpText) {
            helpText.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i>Ready to submit';
            helpText.classList.remove('text-muted');
            helpText.classList.add('text-success');
        }
    } else {
        console.error('Submit button not found for disruption:', disruptionId);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing choice cards...');
    console.log('Found choice cards:', document.querySelectorAll('.choice-card').length);
    console.log('Found forms:', document.querySelectorAll('form[id^="responseForm"]').length);
    console.log('Found submit buttons:', document.querySelectorAll('.submit-response').length);
    
    // Handle choice card selection
    document.querySelectorAll('.choice-card').forEach(card => {
        card.addEventListener('click', function() {
            console.log('Choice card clicked:', this.dataset.choice);
            
            const choice = this.dataset.choice;
            const form = this.closest('form');
            const disruptionId = form.id.replace('responseForm', '');
            
            console.log('Disruption ID:', disruptionId);
            console.log('Form found:', form);
            
            // Remove selection from siblings in the same form
            form.querySelectorAll('.choice-card').forEach(c => c.classList.remove('selected'));
            
            // Add selection to clicked card
            this.classList.add('selected');
            
            // Check the corresponding radio button
            const radio = document.getElementById(choice + disruptionId);
            console.log('Radio button:', radio);
            if (radio) {
                radio.checked = true;
                console.log('Radio checked:', radio.checked);
            }
            
            // Show/hide reschedule options
            const rescheduleOptions = document.getElementById('rescheduleOptions' + disruptionId);
            console.log('Reschedule options element:', rescheduleOptions);
            if (rescheduleOptions) {
                if (choice === 'reschedule') {
                    rescheduleOptions.classList.remove('d-none');
                    console.log('Showing reschedule options');
                } else {
                    rescheduleOptions.classList.add('d-none');
                    console.log('Hiding reschedule options');
                }
            } else {
                console.warn('Reschedule options element not found for ID:', 'rescheduleOptions' + disruptionId);
            }
            
            // Enable submit button
            const submitBtn = form.querySelector('.submit-response');
            const submitHelpText = form.closest('.col-md-4').querySelector('small.text-muted');
            console.log('Submit button:', submitBtn);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-secondary');
                submitBtn.classList.add('btn-primary');
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Response';
                
                // Update help text
                if (submitHelpText) {
                    submitHelpText.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i>Ready to submit';
                    submitHelpText.classList.remove('text-muted');
                    submitHelpText.classList.add('text-success');
                }
                console.log('Submit button enabled');
            }
        });
    });

    // Also handle direct radio button clicks
    document.querySelectorAll('input[name="choice"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                const form = this.closest('form');
                const submitBtn = form.querySelector('.submit-response');
                const submitHelpText = form.closest('.col-md-4').querySelector('small.text-muted, small.text-success');
                
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('btn-secondary');
                    submitBtn.classList.add('btn-primary');
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Response';
                    
                    // Update help text
                    if (submitHelpText) {
                        submitHelpText.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i>Ready to submit';
                        submitHelpText.classList.remove('text-muted');
                        submitHelpText.classList.add('text-success');
                    }
                }
            }
        });
    });

    // Form submission confirmation
    document.querySelectorAll('form[id^="responseForm"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            const choice = this.querySelector('input[name="choice"]:checked')?.value;
            const className = this.closest('.schedule-card').querySelector('.card-title').textContent;
            
            if (!choice) {
                e.preventDefault();
                alert('Please select a response option before submitting.');
                return;
            }
            
            let message = `Are you sure you want to ${choice?.replace('_', ' ')} the class "${className}"?\n\nThis action cannot be undone.`;
            
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});
</script>
@endsection
