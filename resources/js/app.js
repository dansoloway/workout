import Alpine from 'alpinejs';

const QUEUE_KEY = 'morning-workout-queue';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function readQueue() {
    try {
        const parsed = JSON.parse(localStorage.getItem(QUEUE_KEY) || '{}');

        return parsed && typeof parsed === 'object' ? parsed : {};
    } catch {
        return {};
    }
}

function writeQueue(queue) {
    localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
    window.dispatchEvent(new CustomEvent('sync-queue', {
        detail: { count: Object.keys(queue).length },
    }));
}

function appBase() {
    return document.querySelector('meta[name="app-base"]')?.getAttribute('content') ?? '';
}

async function refreshCsrfToken() {
    const response = await fetch(`${appBase()}/csrf-token`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        return null;
    }

    const data = await response.json();
    const meta = document.querySelector('meta[name="csrf-token"]');

    if (meta && data.token) {
        meta.setAttribute('content', data.token);
    }

    return data.token ?? null;
}

async function send(url, completed) {
    const attempt = (token) => fetch(url, {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ completed }),
    });

    let response = await attempt(csrfToken());

    if (response.status === 419) {
        const token = await refreshCsrfToken();

        if (token) {
            response = await attempt(token);
        }
    }

    return response;
}

window.workoutSync = {
    count() {
        return Object.keys(readQueue()).length;
    },

    get(id) {
        return readQueue()[String(id)] ?? null;
    },

    async toggle(url, id, completed) {
        const key = String(id);

        try {
            const response = await send(url, completed);

            if (!response.ok) {
                const error = new Error('Request failed');
                error.status = response.status;
                throw error;
            }

            const queue = readQueue();

            if (queue[key]) {
                delete queue[key];
                writeQueue(queue);
            }

            return {
                queued: false,
                ...(await response.json()),
            };
        } catch (error) {
            if (error.status && error.status < 500) {
                throw error;
            }

            const queue = readQueue();
            queue[key] = {
                url,
                completed: Boolean(completed),
                at: Date.now(),
            };
            writeQueue(queue);

            return {
                queued: true,
                item: { id: Number(id), completed: Boolean(completed) },
                workout: null,
            };
        }
    },

    async flush() {
        const entries = Object.entries(readQueue());

        for (const [id, entry] of entries) {
            try {
                const response = await send(entry.url, entry.completed);

                if (response.status === 419 || response.status >= 500) {
                    break;
                }

                const queue = readQueue();

                if (!response.ok) {
                    delete queue[id];
                    writeQueue(queue);
                    continue;
                }

                if (queue[id] && queue[id].at === entry.at) {
                    delete queue[id];
                    writeQueue(queue);
                }
            } catch {
                break;
            }
        }
    },
};

function createTimer(onDone) {
    return {
        clear() {
            if (this.timer) {
                window.clearInterval(this.timer);
                this.timer = null;
            }
        },

        start() {
            if (this.remaining <= 0) {
                this.remaining = this.total;
            }

            this.running = true;
            this.endsAt = Date.now() + (this.remaining * 1000);
            this.loop();
        },

        pause() {
            this.running = false;
            this.clear();
        },

        resume() {
            if (this.remaining <= 0) {
                return;
            }

            this.start();
        },

        reset() {
            this.pause();
            this.remaining = this.total;
        },

        primary() {
            if (!this.running && this.remaining === this.total) {
                this.start();
                return;
            }

            if (this.running) {
                this.pause();
                return;
            }

            if (this.remaining > 0) {
                this.resume();
                return;
            }

            this.reset();
        },

        loop() {
            this.clear();
            this.timer = window.setInterval(() => {
                const left = Math.max(0, Math.round((this.endsAt - Date.now()) / 1000));
                this.remaining = left;

                if (left > 0) {
                    return;
                }

                this.running = false;
                this.clear();
                onDone(this);
            }, 200);
        },

        destroy() {
            this.clear();
        },
    };
}

function timerFields(seconds) {
    return {
        total: Number(seconds),
        remaining: Number(seconds),
        running: false,
        timer: null,
        endsAt: null,
    };
}

function withTimerGetters(component) {
    return Object.defineProperties(component, {
        display: {
            enumerable: true,
            get() {
                const minutes = Math.floor(this.remaining / 60);
                const secs = this.remaining % 60;

                return `${minutes}:${String(secs).padStart(2, '0')}`;
            },
        },
        showStart: {
            enumerable: true,
            get() {
                return !this.running && this.remaining === this.total;
            },
        },
        showPause: {
            enumerable: true,
            get() {
                return this.running;
            },
        },
        showResume: {
            enumerable: true,
            get() {
                return !this.running && this.remaining < this.total && this.remaining > 0;
            },
        },
        showReset: {
            enumerable: true,
            get() {
                return this.remaining !== this.total;
            },
        },
        primaryLabel: {
            enumerable: true,
            get() {
                if (this.showStart) {
                    return 'Start';
                }

                if (this.showPause) {
                    return 'Pause';
                }

                if (this.showResume) {
                    return 'Resume';
                }

                return 'Reset';
            },
        },
    });
}

document.addEventListener('alpine:init', () => {
    Alpine.data('todayScreen', (config) => ({
        status: config.status,
        done: Number(config.done),
        total: Number(config.total),
        step: Number(config.step),
        labels: config.labels,
        pendingSync: 0,

        next() {
            if (this.step < this.labels.length - 1) {
                this.step += 1;
            }
        },

        back() {
            if (this.step > 0) {
                this.step -= 1;
            }
        },

        init() {
            this.pendingSync = window.workoutSync.count();
            window.addEventListener('sync-queue', (event) => {
                this.pendingSync = event.detail.count;
            });
        },

        applyLocal(event) {
            const completed = event.detail.completed;
            const previous = event.detail.previous;

            if (completed === previous) {
                return;
            }

            this.done += completed ? 1 : -1;
            this.done = Math.min(this.total, Math.max(0, this.done));
            this.status = this.done === 0
                ? 'pending'
                : (this.done === this.total ? 'completed' : 'partial');
        },

        applyServer(event) {
            const workout = event.detail;

            if (!workout || !workout.status) {
                return;
            }

            this.status = workout.status;

            if (typeof workout.done === 'number') {
                this.done = workout.done;
            }

            if (typeof workout.total === 'number') {
                this.total = workout.total;
            }
        },
    }));

    Alpine.data('workoutItem', (config) => withTimerGetters({
        id: config.id,
        name: config.name,
        type: config.type,
        url: config.url,
        completed: Boolean(config.completed),
        busy: false,
        ...timerFields(config.target),
        ...createTimer((component) => {
            if (component.type === 'timed') {
                component.setCompleted(true);
            }
        }),

        init() {
            const queued = window.workoutSync.get(this.id);

            if (!queued || queued.completed === this.completed) {
                return;
            }

            const previous = this.completed;
            this.completed = queued.completed;
            this.$dispatch('item-changed', { completed: this.completed, previous });
        },

        toggle() {
            return this.setCompleted(!this.completed);
        },

        async setCompleted(next) {
            if (this.busy || this.completed === next) {
                return;
            }

            const previous = this.completed;
            this.completed = next;
            this.busy = true;
            this.$dispatch('item-changed', { completed: next, previous });

            try {
                const result = await window.workoutSync.toggle(this.url, this.id, next);

                if (result?.item && result.item.completed !== this.completed) {
                    const before = this.completed;
                    this.completed = result.item.completed;
                    this.$dispatch('item-changed', { completed: this.completed, previous: before });
                }

                if (result?.workout) {
                    this.$dispatch('workout-status', result.workout);
                }
            } catch {
                this.completed = previous;
                this.$dispatch('item-changed', { completed: previous, previous: next });
            } finally {
                this.busy = false;
            }
        },
    }));

    Alpine.data('restTimer', (seconds) => withTimerGetters({
        ...timerFields(seconds),
        ...createTimer(() => {}),
    }));
});

window.Alpine = Alpine;
Alpine.start();
window.workoutSync.flush();
window.addEventListener('online', () => {
    window.workoutSync.flush();
});
