import { FormEvent, useEffect, useState } from 'react';

type Person = {
    id: number;
    name: string;
    title: string;
    team: string;
};

type Entry = {
    id: number;
    title: string;
    hours: number;
    status: string;
    note: string | null;
    details: string;
    source: 'jira' | 'calendar';
    isFullDay?: boolean;
};

type ProjectTab = {
    projectId: number;
    project: string;
    client: string;
    assignedPercent: number;
    totalHours: number;
    tickets: Entry[];
};

type DashboardPayload = {
    today: string;
    people: Person[];
    activeUser: Person & {
        department: string | null;
        joinDate: string | null;
    };
    day: {
        targetHours: number;
        loggedHours: number;
        remainingHours: number;
        projectHours: number;
        meetingHours: number;
        timeOffHours: number;
        allMarked: boolean;
    };
    tabs: {
        projects: ProjectTab[];
        meetings: {
            title: string;
            totalHours: number;
            entries: Entry[];
        };
        timeOff: {
            title: string;
            rule: string;
            totalHours: number;
            entries: Entry[];
        };
    };
    projectOptions: {
        suggested: string[];
        defaultAllocationPercent: number;
    };
    month: {
        label: string;
        numberOfDays: number;
        weekendDays: number;
        workingDays: number;
        totalHours: number;
        loePercent: number;
        columns: string[];
        rows: Array<{
            date: string;
            projects: Record<string, number>;
            totalHours: number;
        }>;
        columnTotals: Record<string, number>;
        columnDays: Record<string, number>;
        columnPercents: Record<string, number>;
        totalDays: number;
    };
    integrations: Array<{
        id: number;
        name: string;
        status: string;
        summary: string;
        isConnected: boolean;
        lastSyncAt: string | null;
    }>;
};

const hourOptions = [0, 0.5, 1, 1.5, 2, 3, 4, 5, 6, 7, 8, 9];

export function App() {
    const isMonthPage = window.location.pathname === '/month';
    const [data, setData] = useState<DashboardPayload | null>(null);
    const [activeTab, setActiveTab] = useState<string>('projects-0');
    const [loading, setLoading] = useState(true);
    const [savingId, setSavingId] = useState<number | null>(null);
    const [addingProject, setAddingProject] = useState(false);

    useEffect(() => {
        void loadDashboard();
    }, []);

    async function loadDashboard(userId?: number) {
        setLoading(true);

        const url = new URL('/api/dashboard', window.location.origin);
        if (userId) {
            url.searchParams.set('user', String(userId));
        }

        const response = await fetch(url.toString());
        const payload = (await response.json()) as DashboardPayload;

        setData(payload);
        setActiveTab((current) => current || (payload.tabs.projects[0] ? 'projects-0' : 'meetings'));
        setLoading(false);
    }

    async function saveEntry(event: FormEvent<HTMLFormElement>, entry: Entry) {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const hours = Number(formData.get('hours'));
        const note = String(formData.get('note') ?? '');

        setSavingId(entry.id);

        await fetch(`/api/entries/${entry.id}/confirm`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ hours, note }),
        });

        await loadDashboard(data?.activeUser.id);
        setSavingId(null);
    }

    async function addProject(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (!data) {
            return;
        }

        const formData = new FormData(event.currentTarget);
        const projectName = String(formData.get('project_name') ?? '').trim();
        const customProjectName = String(formData.get('custom_project_name') ?? '').trim();
        const clientName = String(formData.get('client_name') ?? '').trim();
        const allocationPercent = Number(formData.get('allocation_percent'));
        const finalProjectName = projectName === 'New Project' ? customProjectName : projectName;

        if (!finalProjectName) {
            return;
        }

        setAddingProject(true);

        await fetch('/api/assign-project', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: data.activeUser.id,
                project_name: finalProjectName,
                client_name: clientName || finalProjectName,
                allocation_percent: allocationPercent || data.projectOptions.defaultAllocationPercent,
            }),
        });

        await loadDashboard(data.activeUser.id);
        setAddingProject(false);
        event.currentTarget.reset();
    }

    if (loading || !data) {
        return <div className="loading-shell">Loading LoE tracker...</div>;
    }

    const tabs = [
        ...data.tabs.projects.map((project, index) => ({
            key: `projects-${index}`,
            label: project.project,
            subtitle: `${project.client} - ${project.assignedPercent}% assigned`,
            totalHours: project.totalHours,
            entries: project.tickets,
        })),
        {
            key: 'meetings',
            label: data.tabs.meetings.title,
            subtitle: 'Auto-filled from Google Calendar',
            totalHours: data.tabs.meetings.totalHours,
            entries: data.tabs.meetings.entries,
        },
        {
            key: 'timeOff',
            label: data.tabs.timeOff.title,
            subtitle: 'Auto-filled from Google Calendar',
            totalHours: data.tabs.timeOff.totalHours,
            entries: data.tabs.timeOff.entries,
        },
    ];

    const currentTab = tabs.find((tab) => tab.key === activeTab) ?? tabs[0];
    const monthGridTemplate = `0.9fr repeat(${data.month.columns.length}, minmax(90px, 1fr)) 0.8fr`;

    if (isMonthPage) {
        return (
            <main className="page-shell">
                <section className="hero">
                    <div className="hero-copy">
                        <p className="eyebrow">Monthly LoE</p>
                        <h1>{data.month.label}</h1>
                        <p className="hero-text">Monthly sheet of marked hours by date and project.</p>
                    </div>

                    <div className="hero-panel">
                        <span className="hero-kicker">Employee</span>
                        <div className="employee-card">
                            <div className="employee-card-head">
                                <div className="employee-avatar">{getInitials(data.activeUser.name)}</div>
                                <div>
                                    <strong>{data.activeUser.name}</strong>
                                    <p className="employee-role">{data.activeUser.title}</p>
                                </div>
                            </div>
                            <div className="employee-meta-grid">
                                <div className="employee-meta-item">
                                    <span>Department</span>
                                    <strong>{data.activeUser.department ?? '-'}</strong>
                                </div>
                                <div className="employee-meta-item">
                                    <span>Team</span>
                                    <strong>{data.activeUser.team}</strong>
                                </div>
                                <div className="employee-meta-item employee-meta-wide">
                                    <span>Joining Date</span>
                                    <strong>{formatJoinDate(data.activeUser.joinDate)}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="panel month-panel">
                    <div className="panel-heading">
                        <div>
                            <p className="panel-label">Monthly view</p>
                            <h2>{data.month.label}</h2>
                            <p className="panel-subtitle">Weekend days are excluded</p>
                        </div>
                        <a className="secondary-button" href="/">
                            Back to daily tracker
                        </a>
                    </div>

                    <div className="month-table">
                        <div className="month-row month-row-head" style={{ gridTemplateColumns: monthGridTemplate }}>
                            <strong>Date</strong>
                            {data.month.columns.map((column) => (
                                <strong key={column}>{column}</strong>
                            ))}
                            <strong>Total</strong>
                        </div>
                        {data.month.rows.map((row) => (
                            <div key={row.date} className="month-row" style={{ gridTemplateColumns: monthGridTemplate }}>
                                <span>{row.date}</span>
                                {data.month.columns.map((column) => (
                                    <span key={`${row.date}-${column}`}>{(row.projects[column] ?? 0).toFixed(1)}h</span>
                                ))}
                                <span>{row.totalHours.toFixed(1)}h</span>
                            </div>
                        ))}
                        <div className="month-row month-row-head" style={{ gridTemplateColumns: monthGridTemplate }}>
                            <strong>Total</strong>
                            {data.month.columns.map((column) => (
                                <strong key={`total-${column}`}>{(data.month.columnTotals[column] ?? 0).toFixed(1)}h</strong>
                            ))}
                            <strong>{data.month.totalHours.toFixed(1)}h</strong>
                        </div>
                        <div className="month-row month-row-head" style={{ gridTemplateColumns: monthGridTemplate }}>
                            <strong>Total (days)</strong>
                            {data.month.columns.map((column) => (
                                <strong key={`days-${column}`}>{(data.month.columnDays[column] ?? 0).toFixed(1)}</strong>
                            ))}
                            <strong>{data.month.totalDays.toFixed(1)}</strong>
                        </div>
                        <div className="month-row month-row-head" style={{ gridTemplateColumns: monthGridTemplate }}>
                            <strong>Total (%)</strong>
                            {data.month.columns.map((column) => (
                                <strong key={`pct-${column}`}>{data.month.columnPercents[column] ?? 0}%</strong>
                            ))}
                            <strong>{data.month.loePercent.toFixed(0)}%</strong>
                        </div>
                    </div>
                </section>
            </main>
        );
    }

    return (
        <main className="page-shell">
            <section className="hero">
                <div className="hero-copy">
                    <p className="eyebrow">Daily LoE tracking</p>
                    <h1>LoE tracker for marking your day.</h1>
                    <p className="hero-text">
                        Mark your daily effort across projects, meetings, and time off. Each working day should total 9 hours.
                    </p>
                </div>

                <div className="hero-panel">
                    <span className="hero-kicker">Employee</span>
                    <div className="employee-card">
                        <div className="employee-card-head">
                            <div className="employee-avatar">{getInitials(data.activeUser.name)}</div>
                            <div>
                                <strong>{data.activeUser.name}</strong>
                                <p className="employee-role">{data.activeUser.title}</p>
                            </div>
                        </div>
                        <div className="employee-meta-grid">
                            <div className="employee-meta-item">
                                <span>Department</span>
                                <strong>{data.activeUser.department ?? '-'}</strong>
                            </div>
                            <div className="employee-meta-item">
                                <span>Team</span>
                                <strong>{data.activeUser.team}</strong>
                            </div>
                            <div className="employee-meta-item employee-meta-wide">
                                <span>Joining Date</span>
                                <strong>{formatJoinDate(data.activeUser.joinDate)}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="summary-grid">
                <SummaryCard label="Target" value={`${data.day.targetHours}h`} helper="Required each day" />
                <SummaryCard label="Logged" value={`${data.day.loggedHours.toFixed(1)}h`} helper="Saved so far" />
                <SummaryCard
                    label="Remaining"
                    value={`${data.day.remainingHours.toFixed(1)}h`}
                    helper={data.day.allMarked ? 'Day is fully marked' : 'Still needs employee input'}
                    warning={!data.day.allMarked}
                />
                <SummaryCard label="Projects" value={`${data.day.projectHours.toFixed(1)}h`} helper="Entered manually from Jira tabs" />
                <SummaryCard label="Meetings" value={`${data.day.meetingHours.toFixed(1)}h`} helper="Auto-filled from calendar" />
                <SummaryCard label="Time Off" value={`${data.day.timeOffHours.toFixed(1)}h`} helper="Auto-filled from calendar" />
            </section>

            <div className="view-actions">
                <a className="secondary-button" href="/month" target="_blank" rel="noreferrer">
                    View {data.month.label} data
                </a>
            </div>

            <section className="main-layout">
                <aside className="panel tabs-panel">
                    <p className="panel-label">Tabs</p>
                    <div className="tab-list">
                        {tabs.map((tab) => (
                            <button
                                key={tab.key}
                                type="button"
                                className={`tab-button ${activeTab === tab.key ? 'tab-button-active' : ''}`}
                                onClick={() => setActiveTab(tab.key)}
                            >
                                <strong>{tab.label}</strong>
                                <span>{tab.subtitle}</span>
                                <em>{tab.totalHours.toFixed(1)}h</em>
                            </button>
                        ))}
                    </div>

                    <form className="add-project-box" onSubmit={(event) => void addProject(event)}>
                        <p className="panel-label">Add project</p>
                        <select name="project_name" className="persona-select" defaultValue={data.projectOptions.suggested[0]}>
                            {data.projectOptions.suggested.map((option) => (
                                <option key={option} value={option}>
                                    {option}
                                </option>
                            ))}
                        </select>
                        <input name="custom_project_name" className="note-input" placeholder="Custom project name if needed" />
                        <input name="client_name" className="note-input" placeholder="Client or product name" />
                        <input
                            name="allocation_percent"
                            type="number"
                            min={1}
                            max={100}
                            defaultValue={data.projectOptions.defaultAllocationPercent}
                            className="note-input"
                            placeholder="Allocation %"
                        />
                        <button type="submit" className="primary-button" disabled={addingProject}>
                            {addingProject ? 'Adding...' : 'Add project'}
                        </button>
                    </form>

                    <div className="integration-box">
                        <p className="panel-label">Connected sources</p>
                        {data.integrations.map((integration) => (
                            <div key={integration.id} className="integration-row">
                                <div>
                                    <strong>{integration.name}</strong>
                                    <p>{integration.summary}</p>
                                </div>
                                <span className={`status ${integration.isConnected ? 'status-confirmed' : 'status-draft'}`}>
                                    {integration.status}
                                </span>
                            </div>
                        ))}
                    </div>
                </aside>

                <section className="panel content-panel">
                    <div className="panel-heading">
                        <div>
                            <p className="panel-label">Active tab</p>
                            <h2>{currentTab.label}</h2>
                            <p className="panel-subtitle">{currentTab.subtitle}</p>
                        </div>
                        <span className="chip">{currentTab.totalHours.toFixed(1)}h</span>
                    </div>

                    {activeTab === 'timeOff' ? <p className="rule-box">{data.tabs.timeOff.rule}</p> : null}

                    <div className="entry-list">
                        {currentTab.entries.map((entry) => (
                            <form key={entry.id} className="entry-card" onSubmit={(event) => void saveEntry(event, entry)}>
                                <div className="entry-header">
                                    <div>
                                        <h3>{entry.title}</h3>
                                        <p>{entry.details}</p>
                                    </div>
                                    <span className={`status ${entry.status === 'confirmed' ? 'status-confirmed' : 'status-draft'}`}>
                                        {entry.status}
                                    </span>
                                </div>

                                {entry.source === 'calendar' ? (
                                    <p className="hint-box">This row was auto-populated from Google Calendar.</p>
                                ) : null}

                                {entry.isFullDay ? <p className="full-day-note">Whole-day time off detected. This should fill the full 9-hour day.</p> : null}

                                <div className="confirm-row">
                                    <select name="hours" defaultValue={entry.hours} className="hours-select">
                                        {hourOptions.map((hours) => (
                                            <option key={hours} value={hours}>
                                                {hours}h
                                            </option>
                                        ))}
                                    </select>
                                    <input
                                        name="note"
                                        className="note-input"
                                        defaultValue={entry.note ?? ''}
                                        placeholder="Optional note"
                                    />
                                    <button type="submit" className="primary-button" disabled={savingId === entry.id}>
                                        {savingId === entry.id ? 'Saving...' : 'Save hours'}
                                    </button>
                                </div>
                            </form>
                        ))}
                    </div>
                </section>
            </section>
        </main>
    );
}

function SummaryCard({
    label,
    value,
    helper,
    warning = false,
}: {
    label: string;
    value: string;
    helper: string;
    warning?: boolean;
}) {
    return (
        <div className={`summary-card ${warning ? 'summary-card-warning' : ''}`}>
            <span>{label}</span>
            <strong>{value}</strong>
            <p>{helper}</p>
        </div>
    );
}

function getInitials(name: string) {
    return name
        .split(' ')
        .map((part) => part[0] ?? '')
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

function formatJoinDate(date: string | null) {
    if (!date) {
        return '-';
    }

    return new Date(date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
