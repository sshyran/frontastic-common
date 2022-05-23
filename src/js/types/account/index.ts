// This file is autogenerated – run `ant apidocs` to update it

import {
    ApiDataObject as CoreApiDataObject,
} from '../core/'

export interface Account extends CoreApiDataObject {
     accountId: string;
     email: string;
     salutation?: string;
     firstName?: string;
     lastName?: string;
     birthday?: any /* \DateTime */;
     groups: Group[];
     confirmationToken?: string;
     confirmed?: boolean;
     tokenValidUntil?: any /* \DateTime */;
     addresses: Address[];
     authToken?: string | null;
     apiToken?: string | null;
     /**
      * Access original object from backend
      *
      * This should only be used if you need very specific features
      * right NOW. Please notify Frontastic about your need so that
      * we can integrate those twith the common API. Any usage off
      * this property might make your code unstable against future
      * changes.
      */
     dangerousInnerAccount?: any;
}

export interface Address extends CoreApiDataObject {
     addressId: string;
     salutation?: string;
     firstName?: string;
     lastName?: string;
     streetName?: string;
     streetNumber?: string;
     additionalStreetInfo?: string;
     additionalAddressInfo?: string;
     postalCode?: string;
     city?: string;
     /**
      * 2 letter ISO code (https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2)
      */
     country?: string;
     state?: string;
     phone?: string;
     isDefaultBillingAddress?: boolean;
     isDefaultShippingAddress?: boolean;
     /**
      * Access original object from backend.
      *
      * This should only be used if you need very specific features
      * right NOW. Please notify Frontastic about your need so that
      * we can integrate those with the common API. Any usage off
      * this property might make your code unstable against future
      * changes.
      */
     dangerousInnerAddress?: any;
}

export interface AuthentificationInformation extends CoreApiDataObject {
     email: string;
     password: string;
     newPassword: string;
}

export interface Group extends CoreApiDataObject {
     groupId: string;
     name: string;
     permissions: string[];
}

export interface MetaData extends CoreApiDataObject {
     author: string;
     changed: any /* \DateTimeImmutable */;
}

export interface PasswordResetToken extends CoreApiDataObject {
     email: string;
     confirmationToken?: string | null;
     tokenValidUntil?: any /* \DateTime */ | null;
}

export interface Session extends CoreApiDataObject {
     loggedIn: boolean;
     account?: Account;
     message?: string;
}
