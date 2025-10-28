import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

type OperationType =
    | 'create'
    | 'read'
    | 'read_all'
    | 'edit'
    | 'destroy'
    | 'force_destroy'
    | 'restore'
    | 'enable'
    | 'disable'
    | 'batch_enable'
    | 'batch_disable'
    | 'batch_destroy'
    | null;

export interface Auth {
    user: User;
    menu: Array<string>;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    hasPermission?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: { message: { message: string; title: string; type: string } } | null;
    unreadNotifications: Array<Notification>;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    is_password_set: boolean;
    is_external: boolean;
    disabled_at: string;
    created_at_human?: string | null;
    updated_at_human?: string | null;
    deleted_at_human?: string | null;
    disabled_at_human?: string | null;
    person?: Person;
    active_organizational_units?: Array<OrganizationalUnit>;
    organizational_units?: Array<OrganizationalUnit>;
    roles?: Array<Role>;
    permissions?: Array<Permission>;
}

export interface Person {
    id: number;
    user_id: number;
    id_card: string;
    names: string;
    surnames: string;
    phones: Array<string> | null;
    emails: Array<string> | null;
    position: string;
    staff_type: string;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
    updated_at: string;
    created_at_human?: string;
    updated_at_human?: string;
    deleted_at?: string | null;
    description: string;
}

export interface Permission extends Role {
    set_menu: boolean;
    pivot?: { [index: string]: string | number };
}

export interface Employee {
    company_code?: string;
    nationality?: string;
    id_card: string;
    rif: string;
    names: string;
    surnames: string;
    staff_type_code: string;
    org_unit_code: string;
    position: string;
    staff_type_name: string;
    org_unit_name: string;
}

export interface Can {
    create: boolean;
    read?: boolean;
    update: boolean;
    delete: boolean;
    f_delete?: boolean;
    restore?: boolean;
    enable?: boolean;
    disable?: boolean;
    export?: boolean;
}

export interface PaginatedLink {
    url: string | URL;
    label: string;
    active: boolean;
}

export interface Pagination<T> {
    current_page: number;
    data: Array<T>;
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: Array<PaginatedLink>;
    next_page_url: string;
    path: string;
    per_page: number;
    prev_page_url: string;
    to: number;
    total: number;
}

export interface PaginatedCollectionLinks {
    first: string;
    last: string;
    prev: string;
    next: string;
}

export interface PaginatedCollectionMeta {
    current_page: number;
    from: number;
    last_page: number;
    links: Array<PaginatedLink>;
    path: string;
    per_page: number;
    to: number;
    total: number;
}

export interface PaginatedCollection<T> {
    data: Array<T>;
    links: PaginatedCollectionLinks;
    meta: PaginatedCollectionMeta;
}

export interface SearchFilter {
    [index: string]: string | undefined;
}

export interface Organization {
    id: number;
    rif: string;
    name: string;
    logo_path: string | null;
    logo_url: string | undefined;
    acronym: string | null;
    address: string | null;
    created_at: string | null;
    updated_at: string | null;
    disabled_at: string | null;
    created_at_human: string | null;
    updated_at_human: string | null;
    disabled_at_human: string | null;
    status: string;
    organizational_units?: Array<OrganizationalUnit>;
    active_organizational_units?: Array<OrganizationalUnit>;
}

export interface OrganizationalUnit {
    id: number;
    organization_id: number;
    organizational_unit_id: number;
    code: string;
    name: string;
    acronym: string;
    floor: string;
    created_at: string;
    updated_at: string;
    created_at_human: string | null;
    updated_at_human: string | null;
    disabled_at: string;
    pivot?: { [index: string]: string | null };
    organization: Organization;
    status: string;
    organizational_unit: OrganizationalUnit;
    organizational_units: Array<OrganizationalUnit>;
}

export interface DashboardDataSysadmin {
    activeUsers: Array<{ user: User; ip_address: string; last_active: string }>;
    logSizes: Array<{
        [index: string]: {
            logName: string;
            sizeHuman: string;
            sizeRaw: number;
        };
    }>;
    roles: { count: number; series: Array<number>; labels: Array<string> };
    users: { count: number; series: Array<number>; labels: Array<string> };
}

export interface ActivityLog {
    id: number;
    log_name: string;
    description: string;
    subject_type: string | null;
    subject_id: number | undefined;
    causer_type: string | null;
    causer_id: number;
    causer_name: string | undefined;
    ip_address: string;
    properties: {
        request: {
            ip_address: string;
            user_agent: string;
            user_agent_lang: string;
            referer: string;
            http_method: string;
            request_url: string;
            guard_name?: string;
            remembered?: boolean;
        };
        causer: string | User;
        attributes?: {
            [index: string]: string | number | null;
        };
        old?: {
            [index: string]: string | number | null;
        };
    };
    created_at: string;
    updated_at: string;
    event: string;
    batch_uuid: string | null;
    created_at_human: string;
    updated_at_human: string;
    causer: User;
    subject: User;
}

export interface UserAgent {
    details: {
        platform: string;
        type: string;
        renderer: string;
        browser: string;
        version: string;
    };
    locale: string;
}

export interface NotificationData {
    causer: string;
    message: string;
    photoUrl: string | null;
    timestamp: string;
    url: string;
}

export interface Notification {
    id: string;
    type: string;
    notifiable_type: string;
    notifiable_id: string;
    data: NotificationData;
    read_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}
