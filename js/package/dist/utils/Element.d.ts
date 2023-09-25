export declare namespace Element {
    type CSSStyles = Partial<Omit<{
        [K in keyof CSSStyleDeclaration]-?: K extends string ? CSSStyleDeclaration[K] extends string ? CSSStyleDeclaration[K] : never : never;
    }, 'length' | 'parentRule'>>;
    export type ElementType = HTMLElement | string;
    export const setStyles: (element: HTMLElement, styles: CSSStyles) => void;
    export const getBoxStyles: (element: HTMLElement) => {
        height: number;
        padding: {
            top: number;
            bottom: number;
        };
        border: {
            top: number;
            bottom: number;
        };
    };
    export const getElement: (element: ElementType) => HTMLElement;
    export const setAttribute: (element: HTMLElement, attribute: string, value: string) => void;
    export const getAttribute: (element: HTMLElement, attribute: string) => string | null;
    export {};
}
