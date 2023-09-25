export declare namespace Events {
    const on: (element: HTMLElement, event: string, callback: (event: Event) => void) => {
        destroy: () => void;
    };
}
