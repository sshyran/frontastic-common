// This file is autogenerated – run `ant apidocs` to update it

export interface AttributeFilter {
     name?: string;
     value?: string;
}

export interface ContentType {
     contentTypeId?: string;
     name?: string;
}

export interface Query {
     contentType?: string;
     query?: string;
     contentIds?: any;
     attributes?: AttributeFilter[];
}

export interface Result {
     offset?: number;
     total?: number;
     count?: number;
     items?: any;
}
