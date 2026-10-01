export interface Announcement {
  id: number;
  title: string;
  body: string;
  link_url: string | null;
  audience_type: string;
  show_on_kiosk: boolean;
  created_at: string;
  recipients_count?: number;
  read_count?: number;
}

export interface InboxItem {
  id: number;
  title: string;
  body: string;
  link_url: string | null;
  created_at: string;
  pivot?: { read_at: string | null };
}
