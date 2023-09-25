export declare namespace Types {
    type Options = {
        miliseconds?: number;
        onAnimationEnd?: () => void;
        onAnimationStart?: () => void;
        transitionFunction?: string;
    };
    type ShowOptions = {
        elementDisplayStyle?: CSSStyleDeclaration['display'];
    } & Options;
    type ToggleOptions = {
        onOpen?: () => void;
        onClose?: () => void;
    } & ShowOptions;
}
