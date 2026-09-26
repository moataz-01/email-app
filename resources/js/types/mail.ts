export type MailAccount = {
    id: number;
    name: string;
    email: string;
    provider: 'gmail' | 'imap_smtp';
    status: string;
    last_synced_at: string | null;
    imap_status: string | null;
    smtp_status: string | null;
    last_connection_tested_at: string | null;
};

export type Mailbox = {
    id: number;
    mail_account_id: number;
    name: string;
    kind: string;
};

export type MailFilters = {
    account: number | null;
    mailbox: number | null;
    view: 'inbox' | 'starred' | 'all';
    q: string;
};

export type MailMessage = {
    id: number;
    mail_account_id: number;
    mail_thread_id: number | null;
    subject: string;
    from_name: string | null;
    from_email: string | null;
    snippet: string;
    received_at: string;
    is_read: boolean;
    is_starred: boolean;
    has_attachments: boolean;
};

export type SelectedMessage = MailMessage & {
    text: string | null;
    recipients: { kind: string; name: string | null; email: string }[];
    attachments: {
        id: number;
        filename: string;
        mime_type: string;
        size: number;
    }[];
};

export type MailPage = {
    accounts: MailAccount[];
    mailboxes: Mailbox[];
    filters: MailFilters;
    messages: {
        data: MailMessage[];
        next_cursor: string | null;
        prev_cursor: string | null;
    };
    selectedMessage: SelectedMessage | null;
};
