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

        this.init();
    }

    init() {
        this.initTimer();
        this.initNavigation();
        this.initAnswerListeners();
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
    }

    showQuestion(index) {
        if (index < 0 || index >= this.totalQuestions) return;
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
        // Radio / checkbox selection
        document.querySelectorAll('.answer-input').forEach((input) => {
            input.addEventListener('change', (e) => {
                const qId = parseInt(input.dataset.questionId, 10);
                this.saveQuestionAnswer(qId);
            });
        });

        // Text input responses (on blur or keyup debounce)
        document.querySelectorAll('.text-answer-input').forEach((textarea) => {
            textarea.addEventListener('blur', (e) => {
                const qId = parseInt(textarea.dataset.questionId, 10);
                this.saveQuestionAnswer(qId);
            });
        });
    }

    saveQuestionAnswer(questionId) {
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
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.setSyncStatus('saved');
                this.updateAnswerStats(data.answered_questions, data.total_questions);
                this.updatePaletteView();
            } else {
                this.setSyncStatus('error');
            }
        })
        .catch(() => {
            this.setSyncStatus('error');
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

        this.saveQuestionAnswer(currentQId);
    }

    clearCurrentResponse() {
        const currentQId = this.questionsOrder[this.currentIndex];
        const block = document.getElementById(`question-block-${currentQId}`);
        if (!block) return;

        block.querySelectorAll('.answer-input').forEach(i => i.checked = false);
        const textInput = block.querySelector('.text-answer-input');
        if (textInput) textInput.value = '';

        this.saveQuestionAnswer(currentQId);
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
            if (!document.fullscreenElement && !this.isSubmitting) {
                this.recordViolation('exit_fullscreen', 'Candidate exited full-screen mode.');
            }
        });

        // Tab Switch / Visibility Change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && !this.isSubmitting) {
                this.recordViolation('tab_switch', 'Browser window lost visibility / tab switched.');
            }
        });

        // Window Blur (Focus lost to other apps)
        window.addEventListener('blur', () => {
            if (!this.isSubmitting) {
                this.recordViolation('window_blur', 'Window lost focus to another background application.');
            }
        });
    }

    recordViolation(type, details) {
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
                        window.location.href = window.location.href; // refresh to result
                    });
                } else {
                    alert(data.message);
                    window.location.href = window.location.href;
                }
            } else if (data.status === 'warning') {
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
        });
    }
}

window.CatExamEngine = CatExamEngine;
