/**
 * Production CAT Testing Engine JavaScript
 * Zero external CDN dependencies - 100% Offline Capable
 */

class CatExamEngine {
    constructor(config) {
        this.attemptId = config.attemptId;
        this.remainingSeconds = config.remainingSeconds;
        this.autosaveUrl = config.autosaveUrl;
        this.violationUrl = config.violationUrl;
        this.submitUrl = config.submitUrl;
        this.csrfToken = config.csrfToken;
        this.enforceFullscreen = config.enforceFullscreen;
        this.maxViolations = config.maxViolations;
        this.totalQuestions = config.totalQuestions;
        this.questionsOrder = config.questionsOrder; // array of question IDs
        
        this.currentIndex = 0;
        this.timerInterval = null;
        this.violationCount = config.initialViolations || 0;
        this.isSubmitting = false;
        this.lastViolationTime = 0;
        this.violationCooldownMs = 4000;
        this.isProcessingViolation = false;
        this.hasEnteredFullscreen = false;
        this.heartbeatUrl = config.heartbeatUrl || null;
        this.heartbeatInterval = null;
        this.heartbeatIntervalMs = 25000;
        this.debounceTimers = {};

        this.init();
    }

    init() {
        this.initTimer();
        this.initNavigation();
        this.initAnswerListeners();
        this.initHeartbeat();
        this.initAntiCheat();
        this.updatePaletteView();
        this.showQuestion(0);
    }

    // ----------------------------------------------------
    // Timer System
    // ----------------------------------------------------
    initTimer() {
        const timerDisplay = document.getElementById('timer-display');
        const timerContainer = document.getElementById('timer-container');

        const updateTimer = () => {
            if (this.remainingSeconds <= 0) {
                clearInterval(this.timerInterval);
                this.handleTimeExpiry();
                return;
            }

            const hrs = Math.floor(this.remainingSeconds / 3600);
            const mins = Math.floor((this.remainingSeconds % 3600) / 60);
            const secs = this.remainingSeconds % 60;

            const formatted = [
                hrs > 0 ? String(hrs).padStart(2, '0') : null,
                String(mins).padStart(2, '0'),
                String(secs).padStart(2, '0')
            ].filter(Boolean).join(':');

            if (timerDisplay) {
                timerDisplay.textContent = formatted;
            }

            if (timerContainer) {
                if (this.remainingSeconds <= 60) {
                    timerContainer.classList.add('timer-danger');
                    timerContainer.classList.remove('timer-warning');
                } else if (this.remainingSeconds <= 300) {
                    timerContainer.classList.add('timer-warning');
                }
            }

            this.remainingSeconds--;
        };

        updateTimer();
        this.timerInterval = setInterval(updateTimer, 1000);
    }

    handleTimeExpiry() {
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        if (window.Swal) {
            Swal.fire({
                title: 'Time is Up!',
                text: 'Your allocated time has expired. Your test answers are being submitted automatically.',
                icon: 'warning',
                timer: 3000,
                showConfirmButton: false,
                allowOutsideClick: false,
            }).then(() => {
                document.getElementById('final-submit-form').submit();
            });
        } else {
            alert('Time has expired! Submitting test...');
            document.getElementById('final-submit-form').submit();
        }
    }

    // ----------------------------------------------------
    // Question Flipping & Navigation
    // ----------------------------------------------------
    initNavigation() {
        const btnPrev = document.getElementById('btn-prev');
        const btnNext = document.getElementById('btn-next');
        const btnReview = document.getElementById('btn-toggle-review');
        const btnClear = document.getElementById('btn-clear-response');

        if (btnPrev) {
            btnPrev.addEventListener('click', () => {
                if (this.currentIndex > 0) this.showQuestion(this.currentIndex - 1);
            });
        }

        if (btnNext) {
            btnNext.addEventListener('click', () => {
                if (this.currentIndex < this.totalQuestions - 1) this.showQuestion(this.currentIndex + 1);
            });
        }

        if (btnReview) {
            btnReview.addEventListener('click', () => this.toggleMarkReview());
        }

        if (btnClear) {
            btnClear.addEventListener('click', () => this.clearCurrentResponse());
        }

        // Palette click listeners
        document.querySelectorAll('.palette-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                const targetIndex = parseInt(btn.dataset.index, 10);
                this.showQuestion(targetIndex);
            });
        });

        // Intercept final submit form to flush any pending debounced answers
        const submitForm = document.getElementById('final-submit-form');
        if (submitForm) {
            submitForm.addEventListener('submit', () => {
                this.flushPendingAnswers();
            });
        }
    }

    showQuestion(index) {
        if (index < 0 || index >= this.totalQuestions) return;

        // Flush any pending debounced response on current question before switching
        if (this.questionsOrder && this.questionsOrder[this.currentIndex] !== undefined) {
            const prevQId = this.questionsOrder[this.currentIndex];
            if (this.debounceTimers && this.debounceTimers[prevQId]) {
                clearTimeout(this.debounceTimers[prevQId]);
                delete this.debounceTimers[prevQId];
                this.performSaveAnswer(prevQId);
            }
        }

        this.currentIndex = index;

        // Hide all question panels
        document.querySelectorAll('.question-block').forEach((el) => {
            el.classList.add('d-none');
        });

        // Show active question panel
        const currentQId = this.questionsOrder[this.currentIndex];
        const activeBlock = document.getElementById(`question-block-${currentQId}`);
        if (activeBlock) {
            activeBlock.classList.remove('d-none');
        }

        // Update Prev / Next button states
        const btnPrev = document.getElementById('btn-prev');
        const btnNext = document.getElementById('btn-next');
        if (btnPrev) btnPrev.disabled = (this.currentIndex === 0);
        if (btnNext) {
            if (this.currentIndex === this.totalQuestions - 1) {
                btnNext.classList.add('d-none');
            } else {
                btnNext.classList.remove('d-none');
            }
        }

        this.updatePaletteView();
    }

    // ----------------------------------------------------
    // Response Autosave
    // ----------------------------------------------------
    initAnswerListeners() {
        // Radio / checkbox selection (debounced 300ms to eliminate rapid spamming)
        document.querySelectorAll('.answer-input').forEach((input) => {
            input.addEventListener('change', (e) => {
                const qId = parseInt(input.dataset.questionId, 10);
                this.saveQuestionAnswer(qId, false, 300);
            });
        });

        // Text input responses: input event debounced 600ms, blur saved immediately
        document.querySelectorAll('.text-answer-input').forEach((textarea) => {
            textarea.addEventListener('input', (e) => {
                const qId = parseInt(textarea.dataset.questionId, 10);
                this.saveQuestionAnswer(qId, false, 600);
            });

            textarea.addEventListener('blur', (e) => {
                const qId = parseInt(textarea.dataset.questionId, 10);
                this.saveQuestionAnswer(qId, true);
            });
        });
    }

    saveQuestionAnswer(questionId, immediate = false, delayMs = 350) {
        if (!questionId) return;

        if (this.debounceTimers[questionId]) {
            clearTimeout(this.debounceTimers[questionId]);
            delete this.debounceTimers[questionId];
        }

        if (immediate) {
            this.performSaveAnswer(questionId);
        } else {
            this.setSyncStatus('saving');
            this.debounceTimers[questionId] = setTimeout(() => {
                delete this.debounceTimers[questionId];
                this.performSaveAnswer(questionId);
            }, delayMs);
        }
    }

    performSaveAnswer(questionId) {
        this.setSyncStatus('saving');

        const block = document.getElementById(`question-block-${questionId}`);
        if (!block) return;

        let selectedOptions = [];
        let textAnswer = null;

        // Gather checked radios / checkboxes
        const checkedInputs = block.querySelectorAll('.answer-input:checked');
        checkedInputs.forEach(i => selectedOptions.push(parseInt(i.value, 10)));

        const textInput = block.querySelector('.text-answer-input');
        if (textInput) {
            textAnswer = textInput.value.trim();
        }

        const isMarked = block.dataset.flagged === 'true';

        const payload = {
            question_id: questionId,
            selected_option_ids: selectedOptions.length ? selectedOptions : null,
            text_answer: textAnswer,
            is_marked_for_review: isMarked,
        };

        // Cache locally in localStorage for network resilience
        try {
            localStorage.setItem(`cat_attempt_${this.attemptId}_q_${questionId}`, JSON.stringify(payload));
        } catch (e) {}

        fetch(this.autosaveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(res => {
            if (res.status === 419) {
                this.setSyncStatus('error');
                console.warn('Session or CSRF token expired on autosave.');
                return null;
            }
            return res.json();
        })
        .then(data => {
            if (!data) return;

            if (data.success) {
                this.setSyncStatus('saved');
                this.updateAnswerStats(data.answered_questions, data.total_questions);
                this.updatePaletteView();

                // Synchronize client timer if server indicates large clock drift
                if (typeof data.remaining_seconds === 'number' && Math.abs(data.remaining_seconds - this.remainingSeconds) > 10) {
                    this.remainingSeconds = data.remaining_seconds;
                }
            } else {
                this.setSyncStatus('error');
            }
        })
        .catch(() => {
            this.setSyncStatus('error');
        });
    }

    flushPendingAnswers() {
        if (!this.debounceTimers) return;

        Object.keys(this.debounceTimers).forEach((qId) => {
            clearTimeout(this.debounceTimers[qId]);
            this.performSaveAnswer(parseInt(qId, 10));
        });
        this.debounceTimers = {};
    }

    // ----------------------------------------------------
    // Session Keep-Alive & Real-Time Sync Heartbeat
    // ----------------------------------------------------
    initHeartbeat() {
        if (!this.heartbeatUrl) return;

        this.heartbeatInterval = setInterval(() => {
            this.sendHeartbeat();
        }, this.heartbeatIntervalMs);
    }

    sendHeartbeat() {
        if (this.isSubmitting) return;

        fetch(this.heartbeatUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({}),
        })
        .then(res => {
            if (res.status === 419) {
                console.warn('Heartbeat session ping received 419 (CSRF mismatch).');
                return null;
            }
            return res.json();
        })
        .then(data => {
            if (!data) return;

            // 1. Detect if proctor or system terminated / completed the attempt
            if (data.status && data.status !== 'in_progress') {
                clearInterval(this.timerInterval);
                clearInterval(this.heartbeatInterval);
                this.isSubmitting = true;

                const msg = data.status === 'terminated_violation' 
                    ? 'Your exam was terminated by proctor due to security violations.' 
                    : 'Your exam has been finalized and submitted by the proctor.';

                if (window.Swal) {
                    Swal.fire({
                        title: 'Exam Concluded',
                        text: msg,
                        icon: data.status === 'terminated_violation' ? 'error' : 'info',
                        allowOutsideClick: false,
                        confirmButtonText: 'View Results',
                    }).then(() => {
                        window.location.href = data.result_url || this.submitUrl;
                    });
                } else {
                    alert(msg);
                    window.location.href = data.result_url || this.submitUrl;
                }
                return;
            }

            // 2. Resynchronize clock if proctor added extra time or client drifted
            if (typeof data.remaining_seconds === 'number') {
                const diff = data.remaining_seconds - this.remainingSeconds;
                if (Math.abs(diff) > 5) {
                    this.remainingSeconds = data.remaining_seconds;

                    if (diff > 5) {
                        const addedMins = Math.round(diff / 60);
                        if (window.Swal) {
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 4000,
                                timerProgressBar: true
                            });
                            Toast.fire({
                                icon: 'info',
                                title: `Proctor granted extra time: +${addedMins} min`
                            });
                        }
                    }
                }
            }

            // 3. Update answered count if available
            if (data.answered_count !== undefined && data.total_questions !== undefined) {
                this.updateAnswerStats(data.answered_count, data.total_questions);
            }
        })
        .catch(err => {
            console.warn('Heartbeat ping failed:', err);
        });
    }

    toggleMarkReview() {
        const currentQId = this.questionsOrder[this.currentIndex];
        const block = document.getElementById(`question-block-${currentQId}`);
        if (!block) return;

        const isCurrentlyFlagged = block.dataset.flagged === 'true';
        block.dataset.flagged = (!isCurrentlyFlagged).toString();

        const btnReview = document.getElementById('btn-toggle-review');
        if (btnReview) {
            if (!isCurrentlyFlagged) {
                btnReview.classList.remove('btn-outline-warning');
                btnReview.classList.add('btn-warning');
            } else {
                btnReview.classList.remove('btn-warning');
                btnReview.classList.add('btn-outline-warning');
            }
        }

        this.saveQuestionAnswer(currentQId, true);
    }

    clearCurrentResponse() {
        const currentQId = this.questionsOrder[this.currentIndex];
        const block = document.getElementById(`question-block-${currentQId}`);
        if (!block) return;

        block.querySelectorAll('.answer-input').forEach(i => i.checked = false);
        const textInput = block.querySelector('.text-answer-input');
        if (textInput) textInput.value = '';

        this.saveQuestionAnswer(currentQId, true);
    }

    setSyncStatus(status) {
        const badge = document.getElementById('sync-status-badge');
        if (!badge) return;

        badge.className = 'sync-badge';
        if (status === 'saving') {
            badge.classList.add('sync-saving');
            badge.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
        } else if (status === 'saved') {
            badge.classList.add('sync-saved');
            badge.innerHTML = '<i class="bi bi-cloud-check-fill"></i> Saved';
        } else {
            badge.classList.add('sync-error');
            badge.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Offline/Pending';
        }
    }

    updateAnswerStats(answered, total) {
        const counter = document.getElementById('answered-counter');
        if (counter) counter.textContent = `${answered} / ${total}`;
    }

    updatePaletteView() {
        this.questionsOrder.forEach((qId, idx) => {
            const btn = document.getElementById(`palette-btn-${qId}`);
            const block = document.getElementById(`question-block-${qId}`);
            if (!btn || !block) return;

            btn.className = 'palette-btn';

            // Check if answered
            const hasChecked = block.querySelectorAll('.answer-input:checked').length > 0;
            const textInput = block.querySelector('.text-answer-input');
            const hasText = textInput && textInput.value.trim().length > 0;
            const isAnswered = hasChecked || hasText;
            const isFlagged = block.dataset.flagged === 'true';

            if (isAnswered && isFlagged) {
                btn.classList.add('palette-answered-flagged');
            } else if (isFlagged) {
                btn.classList.add('palette-flagged');
            } else if (isAnswered) {
                btn.classList.add('palette-answered');
            }

            if (idx === this.currentIndex) {
                btn.classList.add('palette-current');
            }
        });
    }

    // ----------------------------------------------------
    // Anti-Cheat & Proctoring Enforcement
    // ----------------------------------------------------
    initAntiCheat() {
        if (!this.enforceFullscreen) return;

        // Prevent Right-Click
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
        });

        // Prevent Copy/Paste
        document.addEventListener('copy', (e) => e.preventDefault());
        document.addEventListener('cut', (e) => e.preventDefault());
        document.addEventListener('paste', (e) => e.preventDefault());

        // Fullscreen Change Detection
        document.addEventListener('fullscreenchange', () => {
            if (document.fullscreenElement) {
                this.hasEnteredFullscreen = true;
            } else if (this.hasEnteredFullscreen && !this.isSubmitting) {
                this.recordViolation('exit_fullscreen', 'Candidate exited full-screen mode.');
            }
        });

        // Tab Switch / Visibility Change (Primary detector for tab-switching and minimizing)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && !this.isSubmitting) {
                this.recordViolation('tab_switch', 'Browser window lost visibility / tab switched.');
            }
        });

        // Window Blur (Focus lost to other OS windows/apps)
        window.addEventListener('blur', () => {
            // Deduplicate: If document is already hidden, visibilitychange already handled it
            if (document.hidden || this.isSubmitting) return;

            // Ignore blur caused by native or Bootstrap in-page dialogs
            if (document.querySelector('.modal.show') || (window.Swal && Swal.isVisible())) return;

            this.recordViolation('window_blur', 'Window lost focus to another background application.');
        });
    }

    recordViolation(type, details) {
        if (this.isSubmitting) return;

        const now = Date.now();
        // Enforce cooldown to prevent rapid event cascading
        if (now - this.lastViolationTime < this.violationCooldownMs || this.isProcessingViolation) {
            return;
        }

        this.lastViolationTime = now;
        this.isProcessingViolation = true;

        fetch(this.violationUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ violation_type: type, details: details }),
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'terminated') {
                this.isSubmitting = true;
                if (window.Swal) {
                    Swal.fire({
                        title: 'Test Terminated',
                        text: data.message,
                        icon: 'error',
                        allowOutsideClick: false,
                        showConfirmButton: true,
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    alert(data.message);
                    window.location.reload();
                }
            } else if (data.status === 'warning') {
                this.violationCount = data.violation_count;
                if (window.Swal) {
                    Swal.fire({
                        title: 'Security Warning!',
                        text: `Warning ${data.violation_count} of ${data.max_violations}: Please remain focused on the exam interface.`,
                        icon: 'warning',
                        toast: true,
                        position: 'top-end',
                        timer: 5000,
                        showConfirmButton: false,
                    });
                }
            }
        })
        .catch(err => {
            console.warn('[AntiCheat] Violation reporting error:', err);
        })
        .finally(() => {
            this.isProcessingViolation = false;
        });
    }
}

window.CatExamEngine = CatExamEngine;
