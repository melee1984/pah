<template>
    <div
        :class="[
            admin ? 'pf-admin-support' : 'pf-support',
            { 'pf-widget': !admin && !fullPage },
        ]"
        :style="
            !admin && !fullPage ? { '--support-bottom': bottom + 'px' } : {}
        "
    >
        <button
            v-if="!admin && !fullPage"
            ref="launcher"
            class="pf-launch"
            :aria-expanded="String(open)"
            aria-controls="pf-support-panel"
            aria-label="Open PahatudFood Support Center"
            @click="toggle"
        >
            <img src="/images/support-tudi.svg" alt="" width="52" height="52" />
            <span class="pf-launch-label">Need help?</span>
            <b v-if="unread" class="pf-dot">{{ unread }}</b>
        </button>
        <transition name="pf-expand">
            <section
                v-if="open || admin || fullPage"
                id="pf-support-panel"
                ref="panel"
                :class="
                    admin ? 'card admin-card dashboard-data-card' : 'pf-panel'
                "
                :role="admin || fullPage ? 'region' : 'dialog'"
                aria-label="PahatudFood Support Center"
                @keydown.esc="close"
                @keydown.tab="trap"
            >
                <header :class="admin ? 'admin-card-header' : 'pf-header'">
                    <div>
                        <small v-if="!admin">PAHATUD CARE</small>
                        <h2>
                            {{ admin ? "Ticket management" : "Support Center" }}
                        </h2>
                        <p v-if="admin">
                            Review requests, assign staff, and follow up with
                            customers.
                        </p>
                    </div>
                    <button
                        v-if="!admin && !fullPage"
                        aria-label="Close support"
                        @click="close"
                    >
                        ×
                    </button>
                </header>
                <div
                    :class="
                        admin
                            ? [
                                  'card-body support-admin-body',
                                  { 'support-admin-detail': view === 'detail' },
                              ]
                            : 'pf-body'
                    "
                >
                    <div v-if="!authenticated" class="pf-welcome">
                        <div class="pf-mascot-scene" aria-hidden="true">
                            <span class="pf-mascot-greeting">Maayong adlaw!</span>
                            <img src="/images/support-tudi.svg" alt="" width="112" height="112" />
                        </div>
                        <span class="pf-welcome-eyebrow">A LITTLE HELP, A LOT OF CARE</span>
                        <h3>Hi, I’m Tudi. Need a hand?</h3>
                        <p>Order questions or a delivery concern? Our support team is here to help.</p>
                        <a class="pf-welcome-action" href="/profile/support">Get support <span aria-hidden="true">→</span></a>
                        <small>Sign in to send a request and keep track of replies.</small>
                    </div>
                    <template v-else>
                        <nav
                            class="pf-tabs"
                            :class="{ 'support-admin-tabs': admin }"
                        >
                            <button
                                v-if="!admin"
                                @click="
                                    view = 'categories';
                                    selected = null;
                                "
                            >
                                New request</button
                            ><button @click="showList">
                                {{
                                    admin
                                        ? "All tickets"
                                        : "My Support Requests"
                                }}
                                <b v-if="unread" class="pf-dot">{{ unread }}</b>
                            </button>
                        </nav>
                        <p v-if="error" role="alert" class="pf-error">
                            {{ error }}
                            <button type="button" @click="retry">Retry</button>
                        </p>
                        <p v-if="notice" role="status" class="pf-success">
                            {{ notice }}
                        </p>
                        <p v-if="loading" role="status">Loading support…</p>
                        <template v-if="view === 'categories' && !admin">
                            <h3>How can we help?</h3>
                            <p>Choose a topic. Our team will follow up here.</p>
                            <button
                                v-for="(category, i) in options.categories"
                                :key="category"
                                class="pf-category"
                                @click="choose(category)"
                            >
                                <span aria-hidden="true">{{ icons[i] }}</span
                                >{{ category }}<span aria-hidden="true">›</span>
                            </button>
                        </template>
                        <form v-if="view === 'create'" @submit.prevent="submit">
                            <h3>{{ form.category }}</h3>
                            <p>
                                Submitting as {{ options.customer.name }} ·
                                {{ options.customer.email }}
                            </p>
                            <label
                                >Subject<input
                                    v-model="form.subject"
                                    required
                                    maxlength="180"
                            /></label>
                            <label v-if="orderApplicable"
                                >Related order (optional)<select
                                    v-model="form.order_id"
                                >
                                    <option value="">No related order</option>
                                    <option
                                        v-for="order in orders"
                                        :key="order.id"
                                        :value="order.id"
                                    >
                                        Order #{{ order.id }}
                                    </option>
                                </select></label
                            >
                            <button
                                v-if="orderApplicable && moreOrders"
                                type="button"
                                @click="loadOrders"
                            >
                                Load older orders
                            </button>
                            <label
                                >Message<textarea
                                    v-model="message"
                                    required
                                    maxlength="10000"
                                    rows="5"
                                    placeholder="Tell us what happened and how we can help."
                                ></textarea>
                            </label>
                            <label
                                >Screenshots (optional)<input
                                    ref="files"
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp"
                                    multiple
                                    @change="filesChanged" /></label
                            ><small
                                >Up to 3 JPG, PNG or WebP images, 2 MB
                                each.</small
                            >
                            <button class="pf-primary" :disabled="saving">
                                {{ saving ? "Submitting…" : "Submit request" }}
                            </button>
                        </form>
                        <template v-if="view === 'list'">
                            <form
                                v-if="admin"
                                class="pf-filters"
                                @submit.prevent="loadList(1)"
                            >
                                <label
                                    >Search<input
                                        v-model="filters.q"
                                        placeholder="Ticket number or subject" /></label
                                ><label
                                    >Customer<input
                                        v-model="filters.customer"
                                        placeholder="Name or email"
                                /></label>
                                <label
                                    v-for="field in [
                                        'category',
                                        'status',
                                        'priority',
                                    ]"
                                    :key="field"
                                    >{{ field
                                    }}<select v-model="filters[field]">
                                        <option value="">All</option>
                                        <option
                                            v-for="value in options[
                                                field === 'category'
                                                    ? 'categories'
                                                    : field === 'status'
                                                    ? 'statuses'
                                                    : 'priorities'
                                            ]"
                                            :key="value"
                                        >
                                            {{ value }}
                                        </option>
                                    </select></label
                                >
                                <label
                                    >From<input
                                        v-model="filters.from"
                                        type="date" /></label
                                ><label
                                    >To<input
                                        v-model="filters.to"
                                        type="date"
                                        :min="filters.from" /></label
                                ><button class="pf-primary">
                                    Apply filters</button
                                ><button
                                    type="button"
                                    @click="
                                        filters = {};
                                        loadList(1);
                                    "
                                >
                                    Clear
                                </button>
                            </form>
                            <div v-if="admin" class="table-responsive">
                                <table
                                    class="table dashboard-data-table support-admin-table"
                                >
                                    <thead>
                                        <tr>
                                            <th>Ticket / Subject</th>
                                            <th>Customer</th>
                                            <th>Category / Order</th>
                                            <th>Assigned staff</th>
                                            <th>Status / Priority</th>
                                            <th>Submitted</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="ticket in tickets"
                                            :key="ticket.id"
                                        >
                                            <td>
                                                <button
                                                    class="dashboard-order-link"
                                                    @click="
                                                        showTicket(ticket.id)
                                                    "
                                                >
                                                    <i
                                                        class="fas fa-ticket-alt"
                                                        aria-hidden="true"
                                                    ></i
                                                    >{{ ticket.number }}
                                                    <b
                                                        v-if="ticket.unread"
                                                        class="pf-dot"
                                                        >Unread</b
                                                    >
                                                </button>
                                                <strong
                                                    class="support-ticket-subject"
                                                    >{{
                                                        ticket.subject
                                                    }}</strong
                                                >
                                            </td>
                                            <td>
                                                {{ ticket.customer_name
                                                }}<small>{{
                                                    ticket.customer_email
                                                }}</small>
                                            </td>
                                            <td>
                                                {{ ticket.category
                                                }}<small>{{
                                                    ticket.order_id
                                                        ? "Order #" +
                                                          ticket.order_id
                                                        : "No order"
                                                }}</small>
                                            </td>
                                            <td>
                                                {{
                                                    staffName(
                                                        ticket.assigned_to
                                                    )
                                                }}
                                            </td>
                                            <td>
                                                <span
                                                    class="dashboard-status-pill"
                                                    :class="
                                                        ticketStatusClass(
                                                            ticket.status
                                                        )
                                                    "
                                                    >{{ ticket.status }}</span
                                                >
                                                <small
                                                    ><span
                                                        class="support-priority"
                                                        :class="{
                                                            'is-urgent': [
                                                                'High',
                                                                'Urgent',
                                                            ].includes(
                                                                ticket.priority
                                                            ),
                                                        }"
                                                        >{{
                                                            ticket.priority
                                                        }}
                                                        priority</span
                                                    ></small
                                                >
                                            </td>
                                            <td>
                                                {{ date(ticket.created_at) }}
                                            </td>
                                        </tr>
                                        <tr v-if="!tickets.length">
                                            <td
                                                colspan="6"
                                                class="dashboard-table-empty"
                                            >
                                                {{
                                                    loading
                                                        ? "Loading support tickets…"
                                                        : "No support requests match your filters."
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <template v-else
                                ><button
                                    v-for="ticket in tickets"
                                    :key="ticket.id"
                                    class="pf-ticket"
                                    @click="showTicket(ticket.id)"
                                >
                                    <small
                                        >{{ ticket.number }}
                                        <b v-if="ticket.unread" class="pf-dot"
                                            >Unread</b
                                        ></small
                                    ><strong>{{ ticket.subject }}</strong
                                    ><span
                                        >{{ ticket.status }} ·
                                        {{ date(ticket.created_at) }}</span
                                    >
                                </button></template
                            >
                            <p v-if="!admin && !tickets.length && !loading">
                                No support requests found.
                            </p>
                            <admin-pagination
                                v-if="admin"
                                :pagination="pagination"
                                @pagination-change-page="loadList"
                            />
                            <div v-else class="pf-pagination">
                                <button
                                    :disabled="page <= 1 || loading"
                                    @click="loadList(page - 1)"
                                >
                                    Previous</button
                                ><span>{{ page }} / {{ lastPage }}</span
                                ><button
                                    :disabled="page >= lastPage || loading"
                                    @click="loadList(page + 1)"
                                >
                                    Next
                                </button>
                            </div>
                        </template>
                        <template v-if="view === 'detail' && selected">
                            <small>{{ selected.number }}</small>
                            <h3>{{ selected.subject }}</h3>
                            <p>
                                {{ selected.category }} · {{ selected.status }}
                                <span v-if="selected.order_id"
                                    >· Order #{{ selected.order_id }}</span
                                >
                            </p>
                            <p v-if="admin">
                                {{ selected.customer_name }} ·
                                {{ selected.customer_email }} ·
                                {{ selected.customer_mobile }}
                            </p>
                            <button @click="showTicket(selected.id)">
                                Refresh conversation
                            </button>
                            <form
                                v-if="admin"
                                class="pf-filters"
                                @submit.prevent="updateTicket"
                            >
                                <label
                                    >Status<select v-model="edit.status">
                                        <option
                                            v-for="s in options.statuses"
                                            :key="s"
                                        >
                                            {{ s }}
                                        </option>
                                    </select></label
                                ><label
                                    >Priority<select v-model="edit.priority">
                                        <option
                                            v-for="p in options.priorities"
                                            :key="p"
                                        >
                                            {{ p }}
                                        </option>
                                    </select></label
                                ><label
                                    >Assign to<select
                                        v-model="edit.assigned_to"
                                    >
                                        <option :value="null">
                                            Unassigned
                                        </option>
                                        <option
                                            v-for="staff in options.staff"
                                            :key="staff.id"
                                            :value="staff.id"
                                        >
                                            {{ staff.name }}
                                        </option>
                                    </select></label
                                ><button class="pf-primary" :disabled="saving">
                                    Save changes
                                </button>
                            </form>
                            <div class="pf-conversation">
                                <article
                                    v-for="entry in messages"
                                    :key="entry.id"
                                    :class="['pf-message', 'pf-' + entry.kind]"
                                >
                                    <strong
                                        >{{
                                            entry.kind === "internal"
                                                ? "Internal note · "
                                                : ""
                                        }}{{ entry.author }}</strong
                                    ><small
                                        >{{ date(entry.created_at) }} ·
                                        {{ entry.kind }}</small
                                    >
                                    <p>{{ entry.body }}</p>
                                    <a
                                        v-for="file in entry.attachments"
                                        :key="file.id"
                                        :href="
                                            base +
                                            '/tickets/' +
                                            selected.id +
                                            '/attachments/' +
                                            file.id
                                        "
                                        >📎 {{ file.name }}</a
                                    >
                                </article>
                            </div>
                            <form
                                v-if="admin || selected.status !== 'Closed'"
                                @submit.prevent="reply"
                            >
                                <label v-if="admin"
                                    ><input
                                        v-model="internal"
                                        type="checkbox"
                                    />
                                    Internal staff note (hidden from
                                    customer)</label
                                ><label
                                    >{{
                                        internal
                                            ? "Internal note"
                                            : "Your reply"
                                    }}<textarea
                                        v-model="message"
                                        required
                                        maxlength="10000"
                                        rows="4"
                                    ></textarea></label
                                ><label
                                    >Attach screenshots<input
                                        ref="files"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        multiple
                                        @change="filesChanged" /></label
                                ><small
                                    >Up to 3 images, 2 MB each. Replying reopens
                                    a resolved request.</small
                                ><button class="pf-primary" :disabled="saving">
                                    {{
                                        saving
                                            ? "Sending…"
                                            : internal
                                            ? "Save internal note"
                                            : "Send reply"
                                    }}
                                </button>
                            </form>
                            <p v-else>
                                This request is closed. Start a new request if
                                you need more help.
                            </p>
                        </template>
                    </template>
                </div>
            </section>
        </transition>
    </div>
</template>
<script>
import axios from "axios";
export default {
    props: { admin: Boolean, authenticated: Boolean, fullPage: Boolean },
    data: () => ({
        open: false,
        view: "categories",
        options: {
            categories: [],
            statuses: [],
            priorities: [],
            customer: {},
            staff: [],
        },
        icons: ["🛵", "💳", "↩", "👤", "💬"],
        form: { category: "", subject: "", order_id: "" },
        message: "",
        files: [],
        orders: [],
        orderPage: 1,
        moreOrders: true,
        filters: {},
        tickets: [],
        page: 1,
        lastPage: 1,
        pagination: {},
        selected: null,
        messages: [],
        edit: {},
        internal: false,
        unread: 0,
        loading: false,
        saving: false,
        error: "",
        notice: "",
        bottom: 24,
    }),
    computed: {
        base() {
            return this.admin ? "/data/dashboard/support" : "/support";
        },
        orderApplicable() {
            return [
                "Orders & Delivery",
                "Billing & Payments",
                "Refunds & Cancellations",
            ].includes(this.form.category);
        },
    },
    mounted() {
        if (this.admin || this.fullPage) {
            this.view = "list";
            this.initialize();
        }
        if (this.authenticated) {
            this.poll();
            this.timer = setInterval(this.poll, 30000);
        }
        if (!this.admin && !this.fullPage) {
            this.position();
            this.positionTimer = setInterval(this.position, 1000);
            window.addEventListener("resize", this.position);
        }
    },
    beforeDestroy() {
        clearInterval(this.timer);
        clearInterval(this.positionTimer);
        window.removeEventListener("resize", this.position);
    },
    methods: {
        ticketStatusClass(status) {
            if (status === "Resolved") return "is-success";
            if (status === "Open" || status === "Waiting for Customer")
                return "is-warning";
            if (status === "In Progress") return "is-danger";
            return "";
        },
        date(value) {
            return new Date(value).toLocaleString();
        },
        staffName(id) {
            const s = this.options.staff.find((s) => s.id === id);
            return s ? s.name : "Unassigned";
        },
        fail(e) {
            this.error = [401, 419].includes(e.response?.status)
                ? "Your session expired. Please sign in again."
                : Object.values(e.response?.data?.errors || {})
                      .flat()
                      .join(" ") ||
                  e.response?.data?.message ||
                  "Unable to connect. Please try again.";
        },
        async initialize() {
            this.loading = true;
            try {
                this.options = (await axios.get(this.base + "/options")).data;
                if (this.admin || this.fullPage) await this.loadList();
            } catch (e) {
                this.fail(e);
            } finally {
                this.loading = false;
            }
        },
        async poll() {
            if (document.hidden) return;
            try {
                this.unread = (
                    await axios.get(this.base + "/alerts")
                ).data.count;
            } catch (_) {
                /* Keep the last known count on network failure. */
            }
        },
        toggle() {
            if (this.open) return this.close();
            this.open = true;
            if (this.authenticated && !this.options.categories.length)
                this.initialize();
            this.$nextTick(() =>
                this.$refs.panel?.querySelector("button, a")?.focus()
            );
        },
        close() {
            if (this.admin || this.fullPage) return;
            this.open = false;
            this.$nextTick(() => this.$refs.launcher.focus());
        },
        trap(event) {
            if (this.admin || this.fullPage) return;
            const nodes = [
                ...this.$refs.panel.querySelectorAll(
                    "button:not(:disabled), a, input, select, textarea"
                ),
            ].filter((n) => n.offsetParent);
            const first = nodes[0],
                last = nodes[nodes.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
        position() {
            let bottom = window.innerWidth < 768 ? 88 : 24;
            document
                .querySelectorAll(
                    ".footer-sticky, .fixed-bottom, .mobile-bottom-nav, .bottom-nav, .scrollToTop"
                )
                .forEach((el) => {
                    const r = el.getBoundingClientRect(),
                        s = getComputedStyle(el);
                    if (
                        r.width &&
                        r.height &&
                        s.display !== "none" &&
                        s.visibility !== "hidden" &&
                        ["fixed", "sticky"].includes(s.position) &&
                        r.bottom >= window.innerHeight - 30 &&
                        r.top > window.innerHeight / 2
                    )
                        bottom = Math.max(
                            bottom,
                            window.innerHeight - r.top + 16
                        );
                });
            this.bottom = Math.min(bottom, window.innerHeight / 2);
        },
        retry() {
            this.error = "";
            if (!this.options.categories.length) this.initialize();
            else if (this.view === "list") this.loadList();
            else if (this.view === "detail") this.showTicket(this.selected.id);
            else if (this.view === "create" && this.orderApplicable)
                this.loadOrders();
        },
        choose(category) {
            this.form.category = category;
            this.form.order_id = "";
            this.view = "create";
            this.message = "";
            this.files = [];
            this.notice = "";
            this.error = "";
            if (this.orderApplicable && !this.orders.length) this.loadOrders();
        },
        async loadOrders() {
            try {
                const data = (
                    await axios.get("/support/orders", {
                        params: { page: this.orderPage },
                    })
                ).data;
                this.orders.push(...data.data);
                this.moreOrders = !!data.next_page_url;
                this.orderPage++;
            } catch (e) {
                this.fail(e);
            }
        },
        filesChanged(event) {
            this.files = [...event.target.files];
            if (
                this.files.length > 3 ||
                this.files.some(
                    (f) =>
                        f.size > 2097152 ||
                        !["image/png", "image/jpeg", "image/webp"].includes(
                            f.type
                        )
                )
            ) {
                this.error =
                    "Choose up to 3 JPG, PNG or WebP images, each no larger than 2 MB.";
                this.files = [];
                event.target.value = "";
            } else this.error = "";
        },
        payload() {
            const data = new FormData();
            data.append("message", this.message);
            this.files.forEach((f) => data.append("attachments[]", f));
            return data;
        },
        async submit() {
            if (this.saving) return;
            this.saving = true;
            this.error = "";
            try {
                const data = this.payload();
                Object.entries(this.form).forEach(([k, v]) =>
                    data.append(k, v)
                );
                const ticket = (await axios.post(this.base + "/tickets", data))
                    .data;
                this.message = "";
                this.files = [];
                this.form.subject = "";
                this.notice =
                    "Request received! Your ticket number is " +
                    ticket.number +
                    ". Track replies here.";
                await this.showTicket(ticket.id);
            } catch (e) {
                this.fail(e);
            } finally {
                this.saving = false;
            }
        },
        showList() {
            this.view = "list";
            this.selected = null;
            this.notice = "";
            this.loadList(1);
        },
        async loadList(page = this.page) {
            this.loading = true;
            this.error = "";
            try {
                const data = (
                    await axios.get(this.base + "/tickets", {
                        params: { ...this.filters, page },
                    })
                ).data;
                this.tickets = data.data;
                this.page = data.current_page;
                this.lastPage = data.last_page;
                this.pagination = {
                    current_page: data.current_page,
                    last_page: data.last_page,
                    total: data.total,
                    from: data.from,
                    to: data.to,
                };
            } catch (e) {
                this.fail(e);
            } finally {
                this.loading = false;
            }
        },
        async showTicket(id) {
            this.loading = true;
            this.error = "";
            try {
                const data = (await axios.get(this.base + "/tickets/" + id))
                    .data;
                if (this.selected?.id !== id) {
                    this.message = "";
                    this.files = [];
                    this.internal = false;
                }
                this.selected = data.ticket;
                this.messages = data.messages;
                this.edit = {
                    status: data.ticket.status,
                    priority: data.ticket.priority,
                    assigned_to: data.ticket.assigned_to,
                };
                this.view = "detail";
                await this.poll();
            } catch (e) {
                this.fail(e);
            } finally {
                this.loading = false;
            }
        },
        async reply() {
            if (this.saving) return;
            this.saving = true;
            this.error = "";
            try {
                const data = this.payload();
                if (this.admin)
                    data.append("internal", this.internal ? "1" : "0");
                await axios.post(
                    this.base + "/tickets/" + this.selected.id + "/replies",
                    data
                );
                this.message = "";
                this.files = [];
                if (this.$refs.files) this.$refs.files.value = "";
                this.notice = this.internal
                    ? "Internal note saved."
                    : "Reply sent.";
                await this.showTicket(this.selected.id);
            } catch (e) {
                this.fail(e);
            } finally {
                this.saving = false;
            }
        },
        async updateTicket() {
            this.saving = true;
            this.error = "";
            try {
                await axios.patch(
                    this.base + "/tickets/" + this.selected.id,
                    this.edit
                );
                this.notice = "Ticket updated.";
                await this.showTicket(this.selected.id);
            } catch (e) {
                this.fail(e);
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
<style>
.pf-support {
    font: 14px/1.5 "DM Sans", sans-serif;
    color: #28252a;
    text-align: left;
}
.pf-widget {
    position: fixed;
    right: 18px;
    bottom: calc(var(--support-bottom) + env(safe-area-inset-bottom, 0px));
    z-index: 1040;
}
.pf-support button,
.pf-support input,
.pf-support select,
.pf-support textarea {
    font: inherit;
}
.pf-support button {
    cursor: pointer;
    border: 1px solid #eadedf;
    border-radius: 9px;
    padding: 9px 12px;
    background: white;
    color: #ad1825;
    min-height: 42px;
}
.pf-support button:disabled {
    opacity: 0.5;
    cursor: default;
}
.pf-support button:focus-visible,
.pf-support a:focus-visible,
.pf-support input:focus,
.pf-support select:focus,
.pf-support textarea:focus {
    outline: 3px solid #f6a8ad;
    outline-offset: 2px;
}
.pf-support .pf-launch,
.pf-support .pf-primary {
    background: #ef3b35;
    color: white;
    border: 0;
    font-weight: 650;
}
.pf-support .pf-launch {
    position: relative;
    width: 68px;
    height: 68px;
    padding: 6px;
    border: 2px solid #fff;
    border-radius: 50%;
    box-shadow: 0 5px 24px #83132440;
    display: flex;
    align-items: center;
    justify-content: center;
}
.pf-launch img {
    display: block;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    object-fit: contain;
}
.pf-launch .pf-dot {
    position: absolute;
    top: -4px;
    right: -4px;
    border: 2px solid white;
    min-width: 22px;
    text-align: center;
}
.pf-support h2,
.pf-support h3 {
    font-family: "Manrope", sans-serif;
}
.pf-panel {
    background: #fff;
    border: 1px solid #eedbdd;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 12px 45px #40132225;
}
.pf-widget .pf-panel {
    position: absolute;
    right: 0;
    bottom: 84px;
    width: min(420px, calc(100vw - 24px));
    max-height: calc(
        100dvh - var(--support-bottom) - 103px -
            env(safe-area-inset-bottom, 0px)
    );
    display: flex;
    flex-direction: column;
}
.pf-header {
    background: #ef3b35;
    color: white;
    padding: 18px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.pf-header h2 {
    color: inherit;
    font-size: 21px;
    margin: 2px 0;
}
.pf-header small {
    color: inherit;
}
.pf-support .pf-header button {
    flex: 0 0 42px;
    width: 42px;
    height: 42px;
    padding: 0;
    line-height: 1;
    background: #ffffff20;
    color: white;
    border: 0;
    font-size: 25px;
}
.pf-launch-label {
    position: absolute;
    right: 78px;
    white-space: nowrap;
    padding: 9px 14px;
    border: 1px solid #eadedf;
    border-radius: 14px 14px 4px 14px;
    background: #fff;
    color: #45332e;
    box-shadow: 0 4px 18px #40132212;
    font-size: 13px;
}
.pf-launch[aria-expanded="true"] .pf-launch-label { display: none; }
.pf-welcome { text-align: center; padding: 4px 6px 10px; }
.pf-mascot-scene {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #fff5e9;
    border-radius: 16px;
    margin-bottom: 20px;
    padding: 12px;
}
.pf-mascot-scene img { display: block; max-width: 45%; height: auto; }
.pf-mascot-greeting {
    background: white;
    color: #795139;
    padding: 9px 12px;
    border-radius: 12px 12px 0 12px;
    font-weight: 650;
    font-size: 13px;
}
.pf-welcome-eyebrow { color: #a14434; font-size: 10px; letter-spacing: 1.4px; font-weight: 700; }
.pf-support .pf-welcome h3 { margin: 8px 0; font-size: 22px; line-height: 1.3; }
.pf-welcome p { color: #6b6262; margin: 0 auto 20px; max-width: 310px; line-height: 1.65; }
.pf-support .pf-welcome-action {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 13px 18px;
    border-radius: 12px;
    background: #c52e29;
    color: white;
    text-decoration: none;
    font-weight: 700;
    min-height: 48px;
}
.pf-welcome-action:hover { background: #a92723; }
.pf-welcome > small { display: block; color: #756a68; font-size: 11px; margin-top: 12px; }
.pf-body {
    padding: 18px;
    overflow-y: auto;
    overscroll-behavior: contain;
}
.pf-support h3 {
    font-size: 19px;
    margin: 18px 0 8px;
}
.pf-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 18px;
}
.pf-category {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 13px;
    margin: 8px 0;
    text-align: left;
    font-weight: 600 !important;
}
.pf-category span:first-child {
    background: #fff0f1;
    border-radius: 10px;
    font-size: 23px;
    width: 42px;
    text-align: center;
    padding: 5px;
}
.pf-category span:last-child {
    margin-left: auto;
}
.pf-support label {
    display: block;
    margin: 12px 0;
    font-weight: 600;
}
.pf-support input:not([type="checkbox"]),
.pf-support select,
.pf-support textarea {
    display: block;
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #d9ced0;
    border-radius: 8px;
    padding: 10px;
    margin-top: 5px;
    background: white;
    color: #28252a;
}
.pf-support input[type="checkbox"] {
    width: auto;
}
.pf-support textarea {
    resize: vertical;
}
.pf-support small {
    display: block;
    font-size: 12px;
}
.pf-primary {
    margin-top: 12px;
}
.pf-dot {
    display: inline-block;
    border-radius: 12px;
    background: #ffe1e5;
    color: #991425;
    padding: 1px 7px;
    font-size: 11px;
}
.pf-ticket {
    display: block;
    text-align: left;
    width: 100%;
    margin: 10px 0;
}
.pf-ticket strong,
.pf-ticket span {
    display: block;
    margin-top: 4px;
}
.pf-ticket span {
    color: #6d6469;
    font-size: 12px;
}
.pf-error {
    background: #fff0f1;
    color: #a11325;
    padding: 12px;
    border-radius: 8px;
}
.pf-success {
    background: #eaf8f0;
    color: #215c40;
    padding: 12px;
    border-radius: 8px;
    overflow-wrap: anywhere;
}
.pf-message {
    padding: 14px;
    border: 1px solid #e8e0e1;
    border-radius: 12px;
    margin: 12px 0;
}
.pf-message p {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    margin: 8px 0;
}
.pf-message a {
    display: block;
    color: #b51b2c;
    overflow-wrap: anywhere;
}
.pf-staff {
    background: #fff2f3;
    border-left: 3px solid #ef3b35;
}
.pf-internal {
    background: #fff7d9;
    border: 1px dashed #ac831a;
}
.pf-event {
    background: #f5f5f5;
}
.pf-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: end;
}
.pf-filters label {
    flex: 1 1 150px;
}
.pf-table-wrap {
    overflow: auto;
}
.pf-support table {
    border-collapse: collapse;
    width: 100%;
    min-width: 850px;
}
.pf-support td,
.pf-support th {
    padding: 14px;
    text-align: left;
    border-bottom: 1px solid #eee;
    vertical-align: top;
}
.pf-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
}
.pf-expand-enter-active,
.pf-expand-leave-active {
    transition: opacity 0.2s, transform 0.2s;
    transform-origin: bottom right;
}
.pf-expand-enter,
.pf-expand-leave-to {
    opacity: 0;
    transform: translateY(15px) scale(0.96);
}
@media (max-width: 767px) {
    .pf-widget {
        right: 12px;
    }
    .pf-body {
        padding: 14px;
    }
    .pf-support input,
    .pf-support select,
    .pf-support textarea {
        font-size: 16px !important;
    }
}
@media (prefers-reduced-motion: reduce) {
    .pf-expand-enter-active,
    .pf-expand-leave-active {
        transition: none;
    }
}
</style>
