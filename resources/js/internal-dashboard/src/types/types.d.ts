declare interface RequestData {
    raw: string;
    method: string;
    uri: string;
    headers: {
      Host: string;
      "User-Agent": string;
      "x-forwarded-for"?: string;
      "x-forwarded-proto"?: string;
      "Content-Type"?: string;
    };
    body?: string;
    query: any[];
    post: any[];
    curl: string;
    plugin?: PluginData;
  }

  declare interface ResponseData {
    status: number;
    reason?: string;
    headers: {
      Server: string;
      "Content-Type": string;
    };
    body: string;
  }

  declare interface CloakrLog {
    id: string;
    performed_at: string;
    duration: number;
    subdomain: string;
    request: RequestData;
    response: ResponseData;
  }

  interface InternalDashboardPageData {
    subdomains: string[];
    user: CloakrUser;
    max_logs: number;
  }

  interface CloakrUser {
    can_specify_subdomains: number;
  }

  interface PostValue {
    name: string
    value: string
  }

  interface ReplayRequest {
    uri: string
    method: string;
    headers: Record<string, string>
    body?: string;
  }

  interface PluginData {
    plugin: string
    uiLabel: string
    cliLabel: string
    details: Record<string, string>
  }
